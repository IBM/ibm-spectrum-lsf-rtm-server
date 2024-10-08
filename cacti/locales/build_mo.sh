#!/bin/sh
#   +-------------------------------------------------------------------------+
#   | Copyright (C) 2004-2024 The Cacti Group                                 |
#   |                                                                         |
#   | This program is free software; you can redistribute it and/or           |
#   | modify it under the terms of the GNU General Public License             |
#   | as published by the Free Software Foundation; either version 2          |
#   | of the License, or (at your option) any later version.                  |
#   |                                                                         |
#   | This program is distributed in the hope that it will be useful,         |
#   | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
#   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
#   | GNU General Public License for more details.                            |
#   +-------------------------------------------------------------------------+
#   | http://www.cacti.net/                                                   |
#   +-------------------------------------------------------------------------+

for file in `ls -1 po/*.po`;do
  ofile=$(basename --suffix=.po ${file})
  echo "Converting $file to LC_MESSAGES/${ofile}.mo"
  msgfmt ${file} -o LC_MESSAGES/${ofile}.mo
done
