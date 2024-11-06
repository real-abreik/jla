<?php

$participant = (int)filter_input(INPUT_GET, 'participant');

$url = "https://www.extra-life.org/index.cfm?fuseaction=donorDrive.participant&participantID=" . $participant . "&_=" . microtime(true);

$html = file_get_contents($url);

$doc = new DOMDocument();

$doc->loadHTML($html);

$div = $doc->getElementById('hospitallogobox');

echo $div->textContent;