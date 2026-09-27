#!/usr/bin/env bash
# Sediakan persekitaran untuk menjana video: pakej Python, font dan rujukan MotionPrompt.
set -e
cd "$(dirname "$0")"
pip install -q pillow numpy imageio-ffmpeg cairosvg playwright
mkdir -p fonts output
if [ ! -d fonts/extras ]; then
  curl -sSfL -o fonts/inter.zip https://github.com/rsms/inter/releases/download/v4.0/Inter-4.0.zip
  unzip -o -q fonts/inter.zip -d fonts && rm fonts/inter.zip
fi
if [ ! -d fonts/pjs ]; then
  curl -sSfL -o fonts/pjs.zip https://github.com/tokotype/PlusJakartaSans/releases/download/2.7.1/PlusJakartaSans-2.7.1.zip
  unzip -o -q fonts/pjs.zip -d fonts/pjs && rm fonts/pjs.zip
fi
# Galeri gaya MotionPrompt (rujukan sahaja, tidak disalin ke repo ini)
[ -d rujukan/motionprompt ] || git clone --depth 1 -q https://github.com/epool86/motionprompt.git rujukan/motionprompt
echo "Sedia. Contoh: python3 scripts/fidyah_vox_40s.py && python3 scripts/fidyah_vox_40s_music.py"
