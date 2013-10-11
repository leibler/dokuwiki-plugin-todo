/**
 * @date 20130405 Leo Eibler <dokuwiki@sprossenwanne.at> \n
 *                replace old sack() method with new jQuery method and use post instead of get - see https://www.dokuwiki.org/devel:jqueryfaq \n
 * @date 20130407 Leo Eibler <dokuwiki@sprossenwanne.at> \n
 *                use jQuery for finding the elements \n
 * @date 20130408 Christian Marg <marg@rz.tu-clausthal.de> \n
 *                change only the clicked todo item instead of all items with the same text \n
 * @date 20130408 Leo Eibler <dokuwiki@sprossenwanne.at> \n
 *                migrate changes made by Christian Marg to current version of plugin (use jQuery) \n
 * @date 20130410 by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at \n
 *                bugfix: encoding html code (security risk <todo><script>alert('hi')</script></todo>) - bug reported by Andreas \n
 * @date 20130413 Christian Marg <marg@rz.tu-clausthal.de> \n
 *                bugfix: chk.attr('checked') returns checkbox state from html - use chk.is(':checked') - see http://www.unforastero.de/jquery/checkbox-angehakt.php \n
 * @date 20130413 by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at \n
 *                bugfix: config option Strikethrough \n
 */

/**
 * html-layout:
 *
 * +input[checkbox].todocheckbox
 * +span.todotext
 * -del
 * --span.todoinnertext
 * ---anchor with text or text only
 */

/**
 * lock to prevent simultanous requests
 */
var todoplugin_locked = { clickspan: false, todo: false };

/**
 * @brief onclick method for span element
 *
 * @param {jQuery} $span  the jQuery span element
 * @param {string} id     the page
 * @param {int}    strike strikethrough activated (1) or not (0) - see config option Strikethrough
 */
function clickSpan($span, id, strike) {
    //skip when locked
    if (todoplugin_locked.clickspan || todoplugin_locked.todo) {
        return;
    }
    //set lock
    todoplugin_locked.clickspan = true;

    //Find the checkbox node we need
    var $chk = $span.prevAll('input.todocheckbox:first');

    if ($chk.is("input")) {
        $chk.prop('checked', !$chk.is(':checked'));
        todo($chk, id, strike);
    } else {
        alert("Appropriate javascript element not found.");
    }

}

/**
 * @brief onclick method for input element
 *
 * @param {jQuery} $chk    the jQuery input element
 * @param {string} path    the page
 * @param {int}    strike  strikethrough activated (1) or not (0) - see config option Strikethrough
 */
function todo($chk) {
    //skip when locked
    if (todoplugin_locked.todo) {
        return;
    }
    //set lock
    todoplugin_locked.todo = true;



    var $spanTodoinnertext = $chk.nextAll("span.todotext:first").find("span.todoinnertext"),
        param = $chk.data(), // contains: index, pageid, date, strikethrough
        checked = !$chk.is(':checked');

    // if the data-index attribute is set, this is a call from the page where the todos are defined
    if (param.index === undefined) param.index = -1;

    if ($spanTodoinnertext.length) {

        var whenCompleted = function (data) {
            //update date after edit and show alert when needed
            if (data.date) {
                jQuery('input.todocheckbox').data('date', data.date);
            }
            if (data.message) {
                alert(data.message);
            }
            //apply styling, or undo checking checkbox
            if (data.succeed) {
                $chk.prop('checked', checked);

                if (checked) {
                    if (param.strikethrough && !$spanTodoinnertext.parent().is("del")) {
                        $spanTodoinnertext.wrap("<del></del>");
                    }
                } else {
                    if ($spanTodoinnertext.parent().is("del")) {
                        $spanTodoinnertext.unwrap();
                    }
                }
            }

            //release lock
            todoplugin_locked = { clickspan: false, todo: false };
        };

        jQuery.post(
            DOKU_BASE + 'lib/exe/ajax.php',
            {
                call: 'plugin_todo',
                index: param.index,
                path: param.pageid,
                checked: checked ? "1" : "0",
                date: param.date
            },
            whenCompleted,
            'json'
        );
    } else {
        alert("Appropriate javascript element not found.\nReverting checkmark.");
    }

}

jQuery(function(){

    // add handler to checkbox
    jQuery('input.todocheckbox').click(function(e){
        e.preventDefault();
        e.stopPropagation();

        var $this = jQuery(this);
        // undo checking the checkbox
        $this.prop('checked', !$this.is(':checked'));

        todo($this);
    });

    // add click handler to todotext spans when marked with 'clickabletodo'
    jQuery('span.todotext.clickabletodo').click(function(){
        //Find the checkbox node we need
        var $chk = jQuery(this).prevAll('input.todocheckbox:first');

        todo($chk);
    });

});
