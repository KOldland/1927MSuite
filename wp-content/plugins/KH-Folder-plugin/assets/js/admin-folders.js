(function ($) {
    'use strict';

    var selectors = {
        createButton: '[data-kh-folders-create]',
        list: '#kh-folders-list',
        notice: '#kh-folders-notices',
        bulkDelete: '[data-kh-folders-bulk-delete]',
        selectAll: '#kh-folders-select-all'
    };

    var state = {
        folders: []
    };

    function notify(message, type) {
        var $notice = $(selectors.notice);
        if (!$notice.length) {
            return;
        }

        $notice.removeClass('is-error is-success').addClass(type === 'error' ? 'is-error' : 'is-success');
        $notice.text(message).fadeIn();

        setTimeout(function () {
            $notice.fadeOut();
        }, 3500);
    }

    function sendAjax(action, data) {
        data = $.extend({}, data, {
            action: action,
            nonce: window.khFoldersAdmin.nonce
        });

        return $.post(window.khFoldersAdmin.ajaxUrl, data)
            .fail(function () {
                notify('Folder service request failed', 'error');
            });
    }

    function esc(value) {
        return $('<div>').text(value).html();
    }

    function folderRowTemplate(folder) {
        return [
            '<tr data-kh-folder-row data-term-id="' + folder.term_id + '">',
            '<td class="column-handle"><span class="kh-folder-drag dashicons dashicons-move" title="' + window.khFoldersAdmin.strings.drag + '"></span></td>',
            '<td>' + esc(folder.name) + '</td>',
            '<td><input type="color" value="' + folder.color + '" data-kh-folder-color="' + folder.term_id + '"></td>',
            '<td><input type="number" class="small-text" value="' + folder.order + '" data-kh-folder-order="' + folder.term_id + '"></td>',
            '<td><button class="button button-link-delete" data-kh-folder-delete="' + folder.term_id + '">' + window.khFoldersAdmin.strings.deleteLabel + '</button></td>',
            '<td class="column-select"><input type="checkbox" data-kh-folder-select="' + folder.term_id + '"></td>',
            '</tr>'
        ].join('');
    }

    function renderList() {
        var $list = $(selectors.list);
        if (!$list.length) {
            return;
        }

        if (!state.folders.length) {
            $list.html('<tr class="no-items"><td colspan="6">' + window.khFoldersAdmin.strings.empty + '</td></tr>');
            destroySortable();
            updateBulkState();
            return;
        }

        var rows = state.folders.map(folderRowTemplate).join('');
        $list.html(rows);
        initSortable();
        updateBulkState();
    }

    function addFolder(folder) {
        folder.order = parseInt(folder.order, 10) || 0;
        state.folders.push(folder);
        state.folders.sort(function (a, b) {
            return a.order - b.order;
        });
        renderList();
    }

    function removeFolder(termId) {
        state.folders = state.folders.filter(function (folder) {
            return folder.term_id !== termId;
        });
        renderList();
    }

    function updateFolder(updated) {
        updated.order = parseInt(updated.order, 10) || 0;
        state.folders = state.folders.map(function (folder) {
            return folder.term_id === updated.term_id ? updated : folder;
        });
        state.folders.sort(function (a, b) {
            return a.order - b.order;
        });
        renderList();
    }

    function destroySortable() {
        var $list = $(selectors.list);
        if ($list && $list.hasClass('ui-sortable')) {
            $list.sortable('destroy');
        }
    }

    function initSortable() {
        var $list = $(selectors.list);
        if (!$list.length) {
            return;
        }

        if ($list.hasClass('ui-sortable')) {
            $list.sortable('destroy');
        }

        $list.sortable({
            handle: '.kh-folder-drag',
            helper: function (event, ui) {
                ui.children().each(function () {
                    $(this).width($(this).width());
                });
                return ui;
            },
            stop: function () {
                var ordered = $list.children('tr').map(function () {
                    return $(this).data('term-id');
                }).get();

                sendAjax('kh_folders_reorder', { order: ordered })
                    .done(function (response) {
                        if (response && response.success && response.data && response.data.folders) {
                            state.folders = response.data.folders;
                            renderList();
                            notify(window.khFoldersAdmin.strings.reordered, 'success');
                        }
                    });
            }
        });
    }

    function handleCreateClick(event) {
        event.preventDefault();

        var folderName = window.prompt(window.khFoldersAdmin.i18n.enterName);
        if (!folderName) {
            return;
        }

        sendAjax('kh_folders_create', { name: folderName })
            .done(function (response) {
                if (!response || !response.success) {
                    notify(response && response.data ? response.data.message || response.data : 'Unknown error', 'error');
                    return;
                }

                addFolder(response.data);
                notify(window.khFoldersAdmin.i18n.created.replace('%s', response.data.name), 'success');
            });
    }

    function handleDeleteClick(event) {
        event.preventDefault();
        var termId = parseInt($(event.currentTarget).data('kh-folder-delete'), 10);
        if (!termId) {
            return;
        }

        sendAjax('kh_folders_delete', { term_id: termId })
            .done(function (response) {
                if (!response || !response.success) {
                    notify(response && response.data ? response.data.message || response.data : 'Unknown error', 'error');
                    return;
                }

                removeFolder(termId);
                notify(window.khFoldersAdmin.strings.deleted, 'success');
            });
    }

    function handleMetaChange(event) {
        var $input = $(event.currentTarget);
        var termId = parseInt($input.data('kh-folder-color') || $input.data('kh-folder-order'), 10);
        if (!termId) {
            return;
        }

        var payload = { term_id: termId };
        if ($input.is('[data-kh-folder-color]')) {
            payload.color = $input.val();
        } else {
            payload.order = $input.val();
        }

        sendAjax('kh_folders_update_meta', payload)
            .done(function (response) {
                if (!response || !response.success) {
                    notify(response && response.data ? response.data.message || response.data : 'Unknown error', 'error');
                    return;
                }

                updateFolder(response.data);
                notify(window.khFoldersAdmin.strings.updated, 'success');
            });
    }

    function getSelectedIds() {
        return $(selectors.list).find('[data-kh-folder-select]:checked').map(function () {
            return parseInt($(this).data('kh-folder-select'), 10);
        }).get();
    }

    function updateBulkState() {
        var selected = getSelectedIds();
        $(selectors.bulkDelete).prop('disabled', selected.length === 0);

        var totalCheckboxes = $(selectors.list).find('[data-kh-folder-select]').length;
        if (!totalCheckboxes) {
            $(selectors.selectAll).prop('checked', false);
            return;
        }

        $(selectors.selectAll).prop('checked', selected.length === totalCheckboxes);
    }

    function handleSelectAll(event) {
        var checked = $(event.currentTarget).is(':checked');
        $(selectors.list).find('[data-kh-folder-select]').prop('checked', checked);
        updateBulkState();
    }

    function handleSelectChange() {
        updateBulkState();
    }

    function handleBulkDelete(event) {
        event.preventDefault();
        var ids = getSelectedIds();
        if (!ids.length) {
            return;
        }

        if (!window.confirm(window.khFoldersAdmin.strings.bulkConfirm)) {
            return;
        }

        sendAjax('kh_folders_bulk_delete', { term_ids: ids })
            .done(function (response) {
                if (!response || !response.success) {
                    notify(response && response.data ? response.data.message || response.data : 'Unknown error', 'error');
                    return;
                }

                var deleted = response.data.deleted || [];
                state.folders = state.folders.filter(function (folder) {
                    return deleted.indexOf(folder.term_id) === -1;
                });
                renderList();
                notify(window.khFoldersAdmin.strings.bulkDeleted, 'success');
            });
    }

    function bindUI() {
        var $createButton = $(selectors.createButton);
        if (!$createButton.length) {
            return;
        }

        window.khFoldersAdmin.strings.deleteLabel = window.khFoldersAdmin.strings.deleteLabel || window.khFoldersAdmin.strings.delete;

        state.folders = window.khFoldersAdmin.folders || [];
        renderList();

        $createButton.on('click', handleCreateClick);
        $(document)
            .on('click', '[data-kh-folder-delete]', handleDeleteClick)
            .on('change', '[data-kh-folder-color], [data-kh-folder-order]', handleMetaChange)
            .on('change', '[data-kh-folder-select]', handleSelectChange)
            .on('click', selectors.bulkDelete, handleBulkDelete)
            .on('change', selectors.selectAll, handleSelectAll);
    }

    $(document).ready(bindUI);
})(jQuery);
