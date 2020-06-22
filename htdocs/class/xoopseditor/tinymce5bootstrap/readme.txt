==============================================================
    TinyMCE v4 pour XOOPS
    V1.10 2013/211/13
    (Alain01)
==============================================================

===============================
TinyMCE V4.10
===============================
- Probl�me des boutons � liste d�roulante : d�calage sur la bas
Rem�de : <!DOCTYPE html> is required for TinyMCE 5 since it's a HTML5 editor
Ne fonctionne pas sous XOOPS... (?)







==============================================================
     Ajout de plugin
==============================================================

===============================
Ajout de QRCode
===============================
https://github.com/cfconsultancy/tinymce5-plugin-qrcode
Version 1.1.0 (2013/10/14)

Edition de qrcode/plugin.min.js :
file:tinyMCE.baseURL+'/plugins/qrcode/qrcode.html'
en
file:'/class/xoopseditor/tinymce5bootstrap/external_plugins/qrcode/qrcode.html'

image:tinyMCE.baseURL+'/plugins/qrcode/icon.png'
en
image:'/class/xoopseditor/tinymce5bootstrap/external_plugins/qrcode/icon.png'

===============================
Ajout de Responsivefilemanager
+
Ajout de Filemanager
===============================
http://responsivefilemanager.com/index.php
Version 9.2.1 (2013/10/28)

configuration + droits : /class/xoopseditor/tinymce5bootstrap/external_plugins/filemanager/config.php

+

configuration par dossier + droits : config.php


===============================
Ajout de Youtube
===============================
https://github.com/gtraxx/tinymce-plugin-youtube
(2013/10/25)
Edition de qrcode/plugin.min.js :
file: tinyMCE.baseURL + '/plugins/youtube/youtube.html'
en
file: b+"/youtube.html"

===============================
Ajout de alignbtn
===============================
permet d'afficher sous forme de liste d�roulante les boutons alignements / gauche / centr� / droit / justifi� /



===============================
Ajout de XOOPS QUOTE
===============================


===============================
Ajout de XOOPS code
===============================

