// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Preview of a integrity statement notice.
 *
 * @module     local_integrity/preview
 * @package    local_integrity
 * @copyright  2021 Catalyst IT
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as Str from 'core/str';
import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import Notification from 'core/notification';
import Templates from 'core/templates';

/**
 * Initialising of the module.
 *
 * @param {String} statementname
 * @param {String} idselector
 */
function init(statementname, idselector) {

    let trigger = document.getElementById(idselector);

    if  (typeof trigger !== 'undefined' ) {

        let noticeRequest = {
            methodname: 'local_integrity_get_statement_notice',
            args: {'name': statementname}
        };

        Ajax.call([noticeRequest])[0].done(function(data) {
            let strings = [
                {key: 'statement:header', component: 'local_integrity'},
                {key: 'statement:save', component: 'local_integrity'},
                {key: 'statement:cancel', component: 'local_integrity'}
            ];

            Str.get_strings(strings).then(function(langStrings) {
                let templateContext = {
                    notice: data.notice,
                };

                return ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    body: Templates.render('local_integrity/statement_form', templateContext),
                    title: langStrings[0],
                    buttons: {
                        save: langStrings[1],
                        cancel: langStrings[2]
                    },
                    isLarge: true,
                }, $(trigger));
            }).catch(Notification.exception);
        }).fail(Notification.exception);
    }
}

export {init};
