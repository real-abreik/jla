<?php

error_reporting(E_ALL);

echo "Bravo Company Lookup\n";
$source = file_get_contents("https://bravocompanyusa.com/bcm-upper-receiver-groups-300-blackout/?_bc_fsnf=1&Barrel+Length%5B%5D=7in&Barrel+Length%5B%5D=9in&Handguard%5B%5D=MCMR-8&Handguard%5B%5D=QRF-8&Handguard%5B%5D=MCMR-7&Handguard%5B%5D=MCMR-5");

if (str_contains($source, "In Stock")) {
	echo "Bravo Company In Stock Found\n";
	$url = "https://discord.com/api/webhooks/1264127903973769218/8TTxE2vrr3eQlH-vkgQVFDXGsb0JPorcvvZ6vaJHOxfoo_Bs07baCUN3tHmh0q7bCPdO";
	$data = [
		'username' => "Bravo Alert",
		'avatar_url' => '',
		'content' => "Something is in stock: " . date("Y-m-d H:i:s")
	];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
}

sleep(800);
