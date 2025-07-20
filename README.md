# Philips Hue Light Controller

A simple PHP command-line tool to control Philips Hue lights using the Philips Hue API.

## Features

- Turn lights on or off
- Set light color using hex values
- Adjust brightness (absolute or relative changes)
- Set color temperature
- List all connected lights and their statuses

## Requirements

- PHP 7.4 or higher
- [Composer](https://getcomposer.org/) for dependency management
- A Philips Hue Bridge with at least one connected light
- A valid configuration file (`config.json`) with bridge IP and API key

## Installation

1. Clone the repository or download the source code:
   ```bash
   git clone https://github.com/mfutselaar/huecontrol
   cd huecontrol
   ```

2. Install dependencies using Composer:
   ```bash
   composer install
   ```

3. Create a `config.json` file in the project root with the following structure:
   ```json
   {
       "bridge_ip": "your_bridge_ip",
       "key": null
   }
   ```
   Replace `your_bridge_ip` with the IP address of your Philips Hue Bridge. The `key` will be automatically generated on first run.

## Usage

Run the script from the command line using:

```bash
php src/index.php <light_name> [color] [options]
```

### Arguments

- `<light_name>`: (Required) The name of the light to control. Use `--list` to see available light names.
- `[color]`: (Optional) A hex color value (e.g., `#FF00FF`) to set the light's color.

### Options

- `--help, -h`: Display help information.
- `--on`: Turn the specified light on.
- `--off`: Turn the specified light off.
- `--lights, --list, -l`: List all connected lights and their statuses.
- `--temperature <int>, -t <int>`: Set the color temperature (within the light's supported range).
- `--brightness <float>, -b <float>`: Set brightness (e.g., `50` for absolute, `+10` or `-20` for relative changes).

When you omit --on or --off, the light will be toggled in whatever the current opposite state is.

### Examples

- List all lights:
  ```bash
  php src/index.php --list
  ```

- Turn a light named "Living Room" on:
  ```bash
  php src/index.php "Living Room" --on
  ```

- Set a light to a specific color:
  ```bash
  php src/index.php "Bedroom Light" #FF0000
  ```

- Adjust brightness by +10:
  ```bash
  php src/index.php "Kitchen Light" --brightness +10
  ```

- Set color temperature to 3000K:
  ```bash
  php src/index.php "Office Light" --temperature 3000
  ```

## First Run

On the first run, if no API key is present in `config.json`, the script will attempt to register with the Philips Hue Bridge:

1. Run the script with any command (e.g., `php src/index.php --list`).
2. If prompted, press the link button on your Philips Hue Bridge.
3. Re-run the command to complete registration. The API key will be saved to `config.json`.

## Configuration

The `config.json` file must include:

- `bridge_ip`: The IP address of your Philips Hue Bridge.
- `key`: The API key (initially `null`, auto-generated on first run).

Example:
```json
{
    "bridge_ip": "192.168.1.100",
    "key": "your_api_key"
}
```

## Tip

Create a symlink in `$HOME/.local/bin` or anywhere else in your `$PATH` to `src/index.php` and chmod +x the symlink.
eg. `ln -s ~/Projects/huecontrol/src/index.php ~/.local/bin/huecontrol && chmod +x ~/.local/bin/huecontrol`. You can
from then on control your Hue system from anywhere by typing `huecontrol livingroom`


## Notes

- Ensure the Philips Hue Bridge is powered on and accessible on the network.
- Color temperature and brightness values are validated against the light's supported range.

## Troubleshooting

- **"Bridge unavailable" error**: Verify the `bridge_ip` in `config.json` and ensure the bridge is powered on.
- **"Link button not pressed" error**: Press the link button on the bridge and re-run the command.
- **Invalid color or temperature**: Ensure hex colors are prefixed with `#` and temperature/brightness values are within valid ranges.