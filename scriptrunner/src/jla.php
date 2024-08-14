<?php

error_reporting(E_ALL);

date_default_timezone_set('America/Chicago');

$start = time();
$elTeamId = 66261;
$cwTeamId = 'jla2024';
$checkWalk = true;
//$webhook = "https://discord.com/api/webhooks/1264139508790853682/ULxQ1NLym0AvN9bupVa7UMxa9DurtmOX1CMZyNgUsemTCfnRUQsyooSmXIGMa-MsFhZZ";
$webhook = "https://discordapp.com/api/webhooks/1264291547273367634/YVBqSW7c6GD3n1Q6Sk-Vg0wC8ic2aTUH7smZWpty_porcckOlG9B8AYVzcwf3FmvVeRo";

$redis = new Redis();
$redis->connect('redis', 6379);


$redis_key = 'jla';

$state_json = $redis->get($redis_key);

if (empty($state_json)) {
	$state = new Storage($start);
} else {
	$state = json_decode($state_json);
}

if (empty($state->timestamp)) {
	$state = new Storage($start);
}

echo "Extra Life Lookup\n";
$el_don_json = file_get_contents("https://extra-life.org/api/1.3/teams/{$elTeamId}/donations?limit=5&offset=0&_=" . time());
$el_don = json_decode($el_don_json);
foreach ($el_don as $donation) {
	if (in_array($donation->donationID, $state->extralife)) {
		break;
	}
	if (empty($state->extralife) || $state->timestamp == $start || $state->timestamp < ($start - 1800)) {
		echo "EL Donation Fault\n";
		echo json_encode($state) . "\n";
		echo "Timestamp: {$start}\n";
		if (empty($state->extralife)) {
			foreach ($el_don as $donation2) {
				$state->extralife[] = $donation2->donationID;
			}
		}
		break;
	}
	$state->extralife[] = $donation->donationID;

	$amount = "$" . number_format($donation->amount);
	$message = "{$donation->recipientName} received a {$amount} donation for Extra Life";
	if (!empty($donation->displayName)) {
		$message .= " from {$donation->displayName}";
	}
	if (!empty($donation->message)) {
		$filtered = str_replace(['*', '_', '`', '~', '/', '>', '#', '-', '[', '('], '', $donation->message);
		$filtered = str_replace(["\n", "\r"], ' ', $filtered);
		$message .= " with the message:\n> {$filtered}";
	}
	echo "Extra Life Donation Found {$donation->donationID}: {$message}\n";

	$data = [
		'username' => "Extra Life Donation Alert",
		'avatar_url' => 'https://ctxfoundation.bswhealth.com/image/Extra-life-bug.png',
		'content' => $message,
	];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $webhook);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
}

if ($checkWalk) {
	echo "CHOC Walk Lookup\n";
	$cw_html = file_get_contents("https://www.chocwalk.org/{$cwTeamId}");
	$raised_raw = get_string_between($cw_html, '<span class="was-raised">', '</span>');
	$raised = filter_var($raised_raw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	$previousRaised = $state->chocwalk->raised;
	if ($raised > 0 && $raised > $previousRaised) {
		$state->chocwalk->raised = $raised;
	}
	$increase = $raised - $previousRaised;
	if ($raised > 0 && $previousRaised > 0 && $increase > 0) {
		$recent_raw = get_string_between($cw_html, 'new RecentDonationsVue(', ').mount(');
		$recent = false;
		if (!empty($recent_raw)) {
			$recent = json_decode($recent_raw);
		}
		$previousDonation = 0;
		$previousName = '';
		if (!empty($recent->recentDonations)) {
			$lastDonation = $recent->recentDonations[0];
			if ($lastDonation->amount > 0) {
				$previousDonation = $lastDonation->amount;
			}
			if (!empty($lastDonation->donor->name)) {
				$previousName = $lastDonation->donor->name;
			} else if ($lastDonation->donor->anonymous) {
				$previousName = "Anonymous";
			}
		}
		if ($previousDonation == $increase && !empty($previousName)) {
			$message = "Received a $" . number_format($increase) . " donation to CHOC Walk from {$previousName}";
		} else {
			$message = "CHOC Walk donation total increased by $" . number_format($increase) . " and the CHOC Walk team total is now $" . number_format($raised) . " thanks to everyone's generous donations.";
		}
		echo "CHOC Walk Donation Found: {$message}\n";

		$data = [
			'username' => "CHOC Walk Donation Alert",
			'avatar_url' => 'https://choc.org/wp-content/uploads/2021/03/cropped-CHOC_Favicon_512px-180x180-1.png',
			'content' => $message,
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $webhook);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
	}
}

$state->timestamp = $start;
$redis->set($redis_key, json_encode($state));
$sleep = 400;
$nap = 30;
$marathonStart = strtotime('first fri of nov');
if (time() >= $marathonStart - 86400 && time() < $marathonStart + (86400 * 4)) {
	$sleep = $nap;
}
$chocWalk = strtotime('first sun of jul');
for ($i = 0; $i < 5; $i++) {
	if (time() >= $chocWalk + (3600 * 5) && time() < $chocWalk + (3600 * 18)) {
		$sleep = $nap;
	}
	$chocWalk += 86400 * 7;
}
if (date('H') >= 18 && date('H') < 23) {
	$sleep = $nap;
}
sleep($sleep);

function get_string_between($haystack, $start, $end) {
	$pos = strpos($haystack, $start);
	if ($pos === false) return false;
	$pos += strlen($start);
	$len = strpos($haystack, $end, $pos) - $pos;
	return substr($haystack, $pos, $len);
}

class Storage {
	public int $timestamp;
	public array $extralife;
	public object $chocwalk;

	public function __construct(int $start) {
		$this->timestamp = $start;
		$this->extralife = [];
		$this->chocwalk = new stdClass(['raised' => 0, 'donations' => [], 'participants' => []]);
	}
}
