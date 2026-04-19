#!/bin/bash

set -e

curl -sS https://get.symfony.com/cli/installer | bash
export PATH="$HOME/.symfony5/bin:$PATH"
ln -sf "$HOME/.symfony5/bin/symfony" /usr/local/bin/symfony