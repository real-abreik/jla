<?php

error_reporting(E_ALL);

$start = time();
$elTeamId = 66261;
$cwTeamId = 'jla2024';

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
	$state->extralife[] = $donation->donationID;
	if (empty($state->extralife) || $state->timestamp == $start || $state->timestamp < ($start - 1800)) {
		continue;
	}

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
	echo "Extra Life Donation Found: {$message}\n";

	$webhook = "https://discord.com/api/webhooks/1264139508790853682/ULxQ1NLym0AvN9bupVa7UMxa9DurtmOX1CMZyNgUsemTCfnRUQsyooSmXIGMa-MsFhZZ";
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

	$webhook = "https://discord.com/api/webhooks/1264139508790853682/ULxQ1NLym0AvN9bupVa7UMxa9DurtmOX1CMZyNgUsemTCfnRUQsyooSmXIGMa-MsFhZZ";
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

$redis->set($redis_key, json_encode($state));
sleep(400);

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
