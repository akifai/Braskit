/*
 * Copyright (C) 2014 Frank Usrs
 *
 * See the LICENSE file for terms and conditions of use.
 */

var BraskitStyle = (function () {
    'use strict';

    if (!window.localStorage) {
        // no localStorage? no style selection for you.
        return null;
    }

    var linkElement = document.getElementById('braskit-style');

    var loadStyle = function (index) {
        if (bss.styles[index]) {
            linkElement.setAttribute('href', bss.styles[index].path);
        }
    };

    var getSelection = function () {
        return localStorage.getItem(bss.storageKey);
    };

    var storeSelection = function (index) {
        localStorage.setItem(bss.storageKey, index);
    };

    var bss = {
        defaultStyle: '{{ style.getDefault()|e("js") }}',

        // event listener for changing the stylesheet. we make it available so
        // plugins can unregister it if desired.
        eventListener: function (event) {
            if (event.key == bss.storageKey) {
                loadStyle(event.newValue);
            }
        },

        getCurrentStyle: function () {
            var userSelected = getSelection();

            if (userSelected !== null) {
                return userSelected;
            }

            return bss.defaultStyle;
        },

        set: function (index) {
            if (!bss.styles[index]) {
                if (window.console) {
                    console.error('No such style', index);
                }

                return;
            }

            storeSelection(index);
            loadStyle(index);
        },

        storageKey: 'braskit-style',

        styles: {{ (style.getJSON() ?: '{}')|raw }}
    };

    // set correct style locally
    loadStyle(getSelection());

    window.addEventListener('storage', bss.eventListener);

    return bss;
})();
