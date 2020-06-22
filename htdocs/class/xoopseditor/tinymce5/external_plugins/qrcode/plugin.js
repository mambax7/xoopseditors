/**
 * QR code - a TinyMCE 4 QR code wizzard
 * qrcode/plugin.js
 *
 *
 * Plugin info: http://www.cfconsultancy.nl/
 * Author: Ceasar Feijen
 *
 * Version: 1.0 released 15/09/2013
 */

tinymce.PluginManager.add('qrcode', function(editor) {

    function openmanager() {
        var title="Create QRcode";
        if (typeof editor.settings.qrcode_title !== "undefined" && editor.settings.qrcode_title) {
            title=editor.settingsqrcode_title;
        }
        win = editor.windowManager.open({
            title: title,
            file: '/class/xoopseditor/tinymce5/external_plugins/qrcode/qrcode.html',
            filetype: 'image',
            width: 550,
            height: 540,
            inline: 1,
            buttons: [{
                text: 'cancel',
                onclick: function() {
                    this.parent()
                        .parent()
                        .close();
                }
            }]
        });

    }
    editor.ui.registry.addButton('qrcode', {
        icon: true,
        image: '/class/xoopseditor/tinymce5/external_plugins/qrcod/icon.png',
        tooltip: 'Create QRcode',
        shortcut: 'Ctrl+QR',
        onclick: openmanager
    });

    editor.addShortcut('Ctrl+QR', '', openmanager);

    editor.ui.registry.addMenuItem('qrcode', {
        icon:'media',
        text: 'Create QRcode',
        shortcut: 'Ctrl+QR',
        onclick: openmanager,
        context: 'insert'
    });
});