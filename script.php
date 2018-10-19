#!/usr/bin/env php
<?php

/*
 * Copyright 2018 Anthony Calabretta
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Needed files from template
const TEMPLATE_FILES = [
	"common/post-fs-data.sh",
	"common/service.sh",
	"common/system.prop",

	"META-INF/com/google/android/update-binary",
	"META-INF/com/google/android/updater-script",

	"config.sh",
];

// A list of Android stock font families and types
const FONT_FAMILIES = [
	"GoogleSans"      => [
		"Bold",
		"BoldItalic",
		"Italic",
		"Medium",
		"MediumItalic",
		"Regular",
	],
	"ProductSans"     => ["Regular"],
	"Roboto"          => [
		"Black",
		"BlackItalic",
		"Bold",
		"BoldItalic",
		"Italic",
		"Light",
		"LightItalic",
		"Medium",
		"MediumItalic",
		"Regular",
		"Thin",
		"ThinItalic",
	],
	"RobotoCondensed" => [
		"Bold",
		"BoldItalic",
		"Italic",
		"Light",
		"LightItalic",
		"Medium",
		"MediumItalic",
		"Regular"
	],
];

// Scan the directory and determine the font family name and types
$newFontFamily = glob("put_fonts_here/*.ttf");
$newFontFamilyName = explode("-", substr($newFontFamily[0], strlen("put_fonts_here/"), -4))[0];
foreach($newFontFamily as &$file){
	$file = explode("-", substr($file, strlen("put_fonts_here/"), -4))[1];
}
// Check if there's the regular font type, which is important
if(!in_array("Regular", $newFontFamily)) die("Regular font type is NEEDED!");

// Some output
echo "Font family: $newFontFamilyName\n\n";

// Let's create the Magisk zip
echo "Creating Magisk zip...\n";
$zip = new ZipArchive();
$res = $zip->open($newFontFamilyName . "-Magisk.zip", ZipArchive::CREATE);
if($res !== true) die("Can't create output file: $res");
// And now let's add the template in it
foreach(TEMPLATE_FILES as $templateFile){
	$zip->addFile("template/" . $templateFile, $templateFile);
	if($zip->status !== ZipArchive::ER_OK) die("Can't add files to new zip!");
}
// Let's add the module.prop with the right values (the one in the template has placeholders)
$prop = file_get_contents("template/module.prop");
$prop = str_replace("{FontNameID}", "MagiskFont_" . strtolower(str_replace(" ", "", $newFontFamilyName)), $prop);
$prop = str_replace("{FontName}", $newFontFamilyName, $prop);
$zip->addFromString("module.prop", $prop);

// Now we can add fonts
echo "Adding fonts...\n";

foreach(FONT_FAMILIES as $familyName => $types){
	foreach($types as $type){
		$name = $familyName . "-" . $type . ".ttf";
		$newName = $newFontFamilyName . "-" . $type . ".ttf";
		if(!is_file("put_fonts_here/$newName")) $newName = $newFontFamilyName . "-Regular.ttf";
		$zip->addFile("put_fonts_here/" . $newName, "system/fonts/" . $name);
		if($zip->status !== ZipArchive::ER_OK) die("Can't add files to new zip!");
		echo $name . " <- " . $newName . PHP_EOL;
	}
}

// And we're done
echo "Done!\n";
