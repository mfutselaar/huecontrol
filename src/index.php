#!/bin/php
<?php

use IoTHome\PhilipsHueApi\Exceptions\BridgeUnavailableException;
use IoTHome\PhilipsHueApi\Exceptions\LinkButtonNotPressedException;
use IoTHome\PhilipsHueApi\Hue\Client;
use ramazancetinkaya\ColorConverter;

const BASE_PATH = __DIR__ . "/..";
const CONFIG_PATH = BASE_PATH . "/config.json";

require_once BASE_PATH . "/vendor/autoload.php";

function printHelp($script): void {
    echo "Usage: php {$script} <name> <color> [--options]" . PHP_EOL . PHP_EOL;
    echo "<name>                 Required: The name of the light, use --list to get a list of all registered names" . PHP_EOL;
    echo "<color>                Optional: The hex value of the color, prefixed with #. Eg. #FF00FF" . PHP_EOL . PHP_EOL;
    echo "Options:" . PHP_EOL;
    echo "        --help         Display this help" . PHP_EOL;
    echo "            -h" . PHP_EOL;
    echo "          --on         Turn the light on" . PHP_EOL;
    echo "         --off         Turn the light off" . PHP_EOL;
    echo "      --lights         Print a list of all connected lights and their statuses" . PHP_EOL;
    echo "        --list" . PHP_EOL;
    echo "            -l" . PHP_EOL;
    echo " --temperature <int>   Set the temperature" . PHP_EOL;
    echo "            -t <int>" . PHP_EOL;
    echo "  --brightness <float> Set the brightness. Prefix the number with +/- to step." . PHP_EOL;
    echo "            -b <float> eg. +10 or -20. This will increase or decrease the brightness by that number" . PHP_EOL;

}
function inArray($val, $array): bool|int
{
    if (is_array($val)) {
        foreach ($val as $value) {
            $test = array_search($value, $array);
            if ($test !== false) {
                return $test;
            }
        }
    } else {
        return array_search($val, $array);
    }
    return false;
}

$config = [
    "key" => null,
    "bridge_ip" => "",
];

$client = null;

$config = json_decode(file_get_contents(CONFIG_PATH), true);
try {
    $client = new Client($config['bridge_ip'], $config["key"]);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

if ($config["key"] === null) {
    try {
        $config["key"] = $client->registerApplication("hue-control");
    } catch (LinkButtonNotPressedException $exception) {
        echo "Press the link button on your hub and run this command again." . PHP_EOL;
        exit(1);
    } catch (BridgeUnavailableException $exception) {
        echo "Bridge on ip {$config["bridge_ip"]} is not available.\nCheck your configuration and if the bridge is actually up!" . PHP_EOL;
        exit(1);
    }
    file_put_contents(CONFIG_PATH, json_encode($config, JSON_PRETTY_PRINT));
}

$lights = $client->getLights();
$name = null;
$color = null;
$temp = null;
$brightness = null;
$status = null;

if ($argc > 1) {
    if (inArray(["-h", "--help"], $argv)) {
        printHelp($argv[0]);
        exit(0);
    }

    if (inArray(["--on"], $argv)) {
        $status = true;
    } elseif (inArray(["--off"], $argv)) {
        $status = false;
    }
    if (inArray(["--lights", "-l", "--list"], $argv) !== false) {
        foreach ($lights as $light) {
            if ($light->isOn()) {
                echo "[ON ] {$light->getName()}: {$light->getRgbColor()} (Brightness {$light->getBrightness()})" . PHP_EOL;
            } else {
                echo "[OFF] {$light->getName()}" . PHP_EOL;
            }
        }
        exit(0);
    }
    $tempIndex = inArray(["--temperature", "-t"], $argv);
    if ($tempIndex !== false) {
        $temp = $argv[$tempIndex + 1];
    }
    $brightnessIndex = inArray(["--brightness", "-b"], $argv);
    if ($brightnessIndex !== false) {
        $brightness = $argv[$brightnessIndex + 1];
    }

    foreach ($argv as $index => $arg) {
        if (str_starts_with($arg, "-")) {
            continue;
        } elseif ($color === null && str_starts_with($arg, "#")) {
            echo $color . PHP_EOL;
            $color = $arg;
        } elseif ($index > 0 && $name === null) {
            $name = $arg;
        }
    }
}

if ($name === null) {
    printHelp($argv[0]);
}

foreach ($lights as $light) {
    if (strtolower($light->getName()) === strtolower($name)) {
        if ($color === null && $brightness === null && $temp === null) {
            if ($status === null) {
                $status = !$light->isOn();
            }
            $newState = $status ? "on" : "off";
            echo "Turning light $name $newState" . PHP_EOL;
            $light->setOn($status);
        } elseif ($light->isOn()) {
            if ($color !== null) {
                $converter = new ColorConverter();
                $rgb = $converter->hexToRgb($color);
                $light->setColorFromRGB($rgb[0], $rgb[1], $rgb[2]);
                echo "Setting color to $color ($rgb[0], $rgb[1], $rgb[2])" . PHP_EOL;
            }
            if ($temp !== null) {
                if ($temp >= $light->getMinColorTemperature() && $temp <= $light->getMaxColorTemperature()) {
                    $light->setColorTemperature($temp);
                    echo "Setting color temperature to $temp" . PHP_EOL;
                }
            }
            if ($brightness !== null) {
                if (str_starts_with($brightness, "+") || str_starts_with($brightness, "-")) {
                    $brightness = $light->getBrightness() + (int)$brightness;
                }
                if ($brightness >= $light->getMinBrightness()) {
                    $light->setBrightness($brightness);
                    echo "Setting brightness: $brightness" . PHP_EOL;
                }
            }
        }
        try {
            $client->updateLight($light);
        } catch (\Exception $exception) {
            echo "Hue threw an error: {$exception->getMessage()}" . PHP_EOL;
        }
    }
}
