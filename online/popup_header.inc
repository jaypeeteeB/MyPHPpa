<?php

/*
 * MyPHPpa
 * Copyright (C) 2003, 2007 Jens Beyer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

include_once "options.php";

$extra_header = "   <TITLE>$game $version</TITLE>\n\n".
"<SCRIPT LANGUAGE=\"javascript\">\n".
"<!--\n".
"// Begin\n".
"function popupWindow(_name, _page, _height, _width) {\n".
"  var window_options = \"toolbar=no, menubar=no, location=no\";\n".
"  window_options += \", scrollbars=no, resize=yes\";\n".
"  window_options += \",height=\" + _height;\n".
"  window_options += \",width=\" + _width;\n".
"  window_options += \",top=\" + (screen.height - _height)/2;\n".
"  window_options += \",left=\" + (screen.width - _width)/2;\n".
"  var OpenWin = open(_page, _name, window_options);\n".
"}\n// END\n//-->\n</SCRIPT>\n";

?>
