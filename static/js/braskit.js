/*
 * braskit.js - Copyright (c) 2013, 2014 Frank Usrs
 *
 * See the LICENSE file for terms and conditions of use.
 */


//
// jQuery helper functions
//

$.fn.extend({
    /**
     * Inserts text in an <input> or <textarea> at the current caret position.
     *
     * @param {string} The text to insert.
     * @returns {jQuery}
     */
    addToInput: function (text) {
        return this.each(function () {
            var length = this.value.length + text.length;

            if (this.createTextRange) {
                var pos = this.caretPos;
                pos.text = text;
            } else if (this.setSelectionRange) {
                var start = this.selectionStart;
                var end = this.selectionEnd;

                this.value = this.value.substr(0, start) + text +
                             this.value.substr(end);

                this.setSelectionRange(length, length);
            } else {
                this.value += text;
            }
        });
    },

    /**
     * Focuses an <input> or <textarea> and places the cursor at the end.
     *
     * @returns {jQuery}
     */
    focusWithCursorAtEnd: function () {
        return this.each(function () {
            var length = this.value.length;

            if (this.createTextRange) {
                var range = this.createTextRange();
                range.moveStart('character', length);
                range.moveEnd('character', length);
                range.select();
            } else if (this.setSelectionRange) {
                this.setSelectionRange(length, length);
            }

            $(this).focus();

            // chrome needs this because it sucks
            $(this).scrollTop(9999999);
        });
    }
});


//
// Callbacks
//

function runCallbacks() {
    var callbacks = $(document.body).data('callback');

    if (!callbacks)
        return;

    callbacks = callbacks.split(/ +/);

    for (var i = 0, j = callbacks.length; i < j; i++)
        if (window[callbacks[i]])
            window[callbacks[i]]();
}

function doStyleSwitchers() {
    if (!BraskitStyle || BraskitStyle.styles.length < 2) {
        // don't show style selector if there aren't at least two styles to
        // choose between
        return;
    }

    // creator selector and add change handler
    var selector = $('<select>').change(function () {
        var selected = $(this).find(':selected').val();
        BraskitStyle.set(selected);
    });

    // add options to selector
    for (var style in BraskitStyle.styles) {
        if (BraskitStyle.styles.hasOwnProperty(style)) {
            var option = $('<option>').val(style);

            // show name for option
            option.text(BraskitStyle.styles[style].name);

            // mark current style as selected
            if (style == BraskitStyle.getCurrentStyle()) {
                option.prop('selected', true);
            }

            selector.append(option);
        }
    }

    $('.style-list').html(selector);
    $('.style-unhide').removeClass('no-screen');
}

function doConfig() {
    // toggle default value
    $('.toggle-reset').change(function () {
        var key = 'config_' + this.name.match(/\[(.*)\]/)[1];
        var inputField = $('#' + key);

        // enable/disable the input field as appropriate
        inputField.prop('disabled', this.checked);

        // handle boolean options
        if (inputField.attr('type') == 'checkbox') {
            inputField.prop('checked', !inputField.prop('checked'));
            return;
        }

        // set the SQL-stored input so we can retrieve it
        // gets run the first time a checkbox is ticked
        if (this.checked && !$(this).data('has-sql-stored')) {
            $(this).data('sql-stored', inputField.attr('value'));

            // fucking weak typing...
            $(this).data('has-sql-stored', true);
        }

        // we're ticking the checkbox
        var dataSource = this.checked ? 'default' : 'sql-stored';

        inputField.attr('value', $(this).data(dataSource));
    });
}

function highlightPost(num) {
    $('.highlighted').removeClass('highlighted');
    $('#' + num).addClass('highlighted');
}

function doReplyPage() {
    var textarea = $('#postform textarea[name=field4]');

    $('.ref-link.no').click(function () {
        var num = $(this).data('num');
        highlightPost(num);
    });

    $('.ref-link.val').click(function () {
        var num = $(this).data('num');
        textarea.addToInput('>>' + num + '\n');
        textarea.focus();
    });

    var matches = window.location.hash.match(/^#(i)?(\d+)$/);

    if (!matches) {
        return;
    }

    var doInsert = typeof matches[1] != 'undefined';
    var num = matches[2];

    // Add stuff to textarea
    if (doInsert && !textarea.val()) {
        textarea.addToInput('>>' + num + '\n');
        textarea.focus();

        return;
    }

    // Highlight post
    highlightPost(num);
}

function delformSubmit() {
    var cookie = $.cookie('password');

    if (cookie === undefined) {
        cookie = '';
    }

    var password = $('<input>', {
        type: 'hidden',
        name: 'password',
        value: cookie
    });

    $(this).append(password);
}

function getCSRF(success) {
    // TODO: load CSRF from ajax
    var csrf = $('body').data('csrf');

    if (csrf !== undefined) {
        success(csrf);
        return;
    }

    console.error('Unable to retrieve CSRF token.');
}

function doReportDismissal(event, url) {
    var link = $(this);
    var listItem = link.parents('li');
    var reportList = listItem.parents('.post-reports');

    getCSRF(function (csrf) {
        $.post(url, { 'csrf': csrf }, function (data) {
            if (data.error) {
                console.error(data.errorMsg);
                return;
            }

            // get element to remove
            var el = (reportList.find('li').length > 1) ? listItem : reportList;

            // fade out
            el.fadeOut({
                done: function () {
                    el.remove();
                }
            });

            // change the click handler for the dismiss link
            link.off('click').on('click', function (event) {
                // do nothing
                event.preventDefault();
            });
        }, 'json');
    });
}


/*
 * AJAX dialogue boxes
 *
 * We could have used the modals from bootstrap, but I don't really like them.
 */

function Dialogue(url, orig) {
    this.url = url;

    // where to redirect if things fail
    this.defaultURL = orig;

    this.createScreen();
    this.createSpinner();

    var self = this;

    // loads the page using AJAX
    $.getJSON(this.url, function (data) {
        // success - display the page
        self.createWindow(data);
    }).fail(function () {
        self.handleError();
    });
}

Dialogue.prototype.handleError = function () {
    console.error('Couldn\'t load the page for some reason.');

    var href = this.defaultURL;
    this.destroy();

    // redirect to the original location of the href
    window.location = href;
};

Dialogue.prototype.createScreen = function () {
    this.container = document.createElement('div');
    this.screen = document.createElement('div');

    var self = this;

    $(this.screen).addClass('dl-screen').click(function () {
        self.destroy();
    });

    $(this.container)
        .addClass('ajax-modal-container')
        .append(this.screen);

    $('#wrapper').after(this.container);
    $(this.screen).fadeIn();
};

Dialogue.prototype.createWindow = function (data) {
    this.spinner.stop();

    var win = $(document.createElement('div')).addClass('dl-window');
    win.html(data.page);
    win.css('display', 'none');

    $(this.container).append(win);
    win.fadeIn();

    win.find('.focus').first().focus();
};

Dialogue.prototype.createSpinner = function () {
    this.spinner = new Spinner({
        lines: 9,
        length: 6,
        width: 3,
        radius: 4,
        hwaccel: true,
        color: '#ccc'
    }).spin(this.screen);
};

Dialogue.prototype.destroy = function () {
    // Fades out, then removes the container and all its child nodes
    $(this.container).fadeOut({
        done: function () {
            $(this).remove();
        }
    });
};


//
// Global init
//

$(function () {
    // run page-specific callbacks
    runCallbacks();

    // Create style switchers
    doStyleSwitchers();

    // Focus stuff
    $('.focus').first().focus();

    $('[data-ajax]').click(function (event) {
        event.preventDefault();

        var handler = $(this).data('handler');
        var loadUrl = $(this).data('ajax');
        var original = $(this).attr('href');

        if (handler === undefined) {
            new Dialogue(loadUrl, original);
        } else if (window[handler]) {
            window[handler].apply(this, [event, loadUrl, original]);
        }
    });

    $('[name=delform]').submit(delformSubmit);
});
