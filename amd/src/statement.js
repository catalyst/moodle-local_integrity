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
 * Bulk actions for lists of participants.
 *
 * @module     local_integrity/statement
 * @copyright  2021 Catalyst IT
 * @author     Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Str from 'core/str';
import Ajax from 'core/ajax';
import ModalEvents from 'core/modal_events';
import ModalFactory from 'core/modal_factory';
import Notification from 'core/notification';
import Templates from 'core/templates';
import KeyCodes from 'core/key_codes';

/**
 * Initialising of the module.
 *
 * @param {Integer} contextid Context ID,
 * @param {String} statementname
 * @param {String} cancelurl URL to redirect if cancelled.
 */
function init(contextid, statementname, cancelurl) {

    self.contextid = contextid;
    self.statementname = statementname;
    self.cancelurl = cancelurl;
    self.submitted = false;

    document.addEventListener('keyup', escCloseListener);

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
                removeOnClose: true,
                isLarge: true,
            });
        }).then(function(modal) {
            modal.getRoot().on(ModalEvents.save, (e) => agreeStatement(e, modal));
            modal.getRoot().on(ModalEvents.destroyed, () => handleRedirect());
            modal.getRoot().on(ModalEvents.hidden, () => handleRedirect());
            modal.getRoot().on(ModalEvents.cancel, () => handleRedirect());

            modal.show();
        }).catch(Notification.exception);
    }).fail(Notification.exception);
}

/**
 * Listen to escape button pushed.
 * @param {Event} e
 */
function escCloseListener(e) {
    if (e.keyCode === KeyCodes.escape) {
        handleRedirect();
    }
}

/**
 * Handle redirect action.
 */
function handleRedirect() {
    if (self.submitted === false) {
        window.location.replace(self.cancelurl);
    }
}

/**
 * Submit statement agreement.
 *
 * @param {Event} e
 * @param {Modal} modal
 */
function agreeStatement(e, modal) {
    const agreed = modal.getRoot().find('form input').prop('checked');

    e.preventDefault();

    if (agreed === false) {
        modal.getRoot().find('[data-role="agreementrequired"]').removeAttr('hidden');
        return;
    }

    const args = {
        'name': self.statementname,
        'contextid': self.contextid,
        'userid': 0,
    };

    Ajax.call([{
        methodname: 'local_integrity_agree_statement',
        args: args,
        done: function () {
            self.submitted = true;
            document.removeEventListener('keyup', escCloseListener);
            modal.destroy();
        },
        fail: function (response) {
            Notification.exception(response);
        }
    }]);
}

export {init};
