jQuery(document).ready(function($) {
    'use strict';

    var partSize = khomanguonR2Upload.partSize || 25 * 1024 * 1024;
    var continuationToken = '';

    function getProvider() {
        return $('#khomanguon-cloud-provider').val() || 'r2';
    }

    function getProviderConfig() {
        var providers = khomanguonR2Upload.providers || {};

        return providers[getProvider()] || {};
    }

    function request(action, data) {
        return $.ajax({
            url: khomanguonR2Upload.ajaxUrl,
            method: 'POST',
            data: $.extend({
                action: action,
                nonce: khomanguonR2Upload.nonce,
                provider: getProvider()
            }, data || {})
        });
    }

    function setStatus(message, isError) {
        $('#khomanguon-r2-upload-status')
            .text(message || '')
            .toggleClass('is-error', !!isError);
    }

    function setProgress(percent) {
        var normalized = Math.max(0, Math.min(100, percent));
        $('.khomanguon-r2-progress').attr('aria-hidden', normalized <= 0 ? 'true' : 'false');
        $('#khomanguon-r2-progress-bar').css('width', normalized + '%').text(Math.round(normalized) + '%');
    }

    function normalizeKey(value) {
        return (value || '')
            .replace(/\\/g, '/')
            .replace(/^\/+/, '')
            .replace(/\/+/g, '/')
            .trim();
    }

    function joinKey(prefix, fileName) {
        prefix = joinPrefix(getProviderConfig().prefix || '', prefix);
        fileName = normalizeKey(fileName);

        if (prefix && prefix.charAt(prefix.length - 1) !== '/') {
            prefix += '/';
        }

        return prefix + fileName;
    }

    function joinPrefix(basePrefix, childPrefix) {
        basePrefix = normalizeKey(basePrefix);
        childPrefix = normalizeKey(childPrefix);

        if (basePrefix && basePrefix.charAt(basePrefix.length - 1) !== '/') {
            basePrefix += '/';
        }

        return basePrefix + childPrefix;
    }

    function formatSize(bytes) {
        var units = ['B', 'KB', 'MB', 'GB', 'TB'];
        var size = Number(bytes) || 0;
        var unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size = size / 1024;
            unitIndex++;
        }

        return size.toFixed(unitIndex === 0 ? 0 : 2) + ' ' + units[unitIndex];
    }

    function getFilePath(key) {
        var bucket = normalizeKey(getProviderConfig().bucket || '');

        return bucket ? bucket + '/' + normalizeKey(key) : normalizeKey(key);
    }

    function copyText(value) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value);
        } else {
            var tempInput = $('<textarea readonly></textarea>').val(value).appendTo('body');
            tempInput[0].select();
            document.execCommand('copy');
            tempInput.remove();
        }
    }

    function toggleFilePathColumn() {
        var visible = $('#khomanguon-r2-show-file-path').is(':checked');
        $('.khomanguon-r2-path-column').toggle(visible);
    }

    function formatCash(amount) {
        return (Number(amount) || 0).toLocaleString('vi-VN') + ' @Cash';
    }

    function updateProviderState() {
        var config = getProviderConfig();
        var isConfigured = !!config.configured;
        var prefix = config.prefix || '';
        var disabled = !isConfigured;

        $('#khomanguon-r2-file, #khomanguon-r2-prefix, #khomanguon-r2-key, #khomanguon-r2-start-upload, #khomanguon-r2-list-prefix, #khomanguon-r2-refresh, #khomanguon-r2-apply-cors, #khomanguon-r2-show-file-path').prop('disabled', disabled);
        $('#khomanguon-r2-key-help').text('Upload prefix hiện tại: ' + (prefix || '/') + '. Key được lưu theo provider ' + (config.label || getProvider()) + '.');

        if (disabled) {
            $('#khomanguon-r2-files-body').html('<tr><td colspan="9">Provider này chưa được cấu hình nên màn hình đang bị vô hiệu hoá.</td></tr>');
            updateTotals(0, 0, 0);
        }
    }

    function updateTotals(fileCount, downloadCount, revenue) {
        $('#khomanguon-r2-total-files').text((Number(fileCount) || 0).toLocaleString('vi-VN'));
        $('#khomanguon-r2-total-downloads').text((Number(downloadCount) || 0).toLocaleString('vi-VN'));
        $('#khomanguon-r2-total-revenue').text(formatCash(revenue));
    }

    function uploadPart(url, blob) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();

            xhr.open('PUT', url, true);

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var etag = xhr.getResponseHeader('ETag');
                    if (!etag) {
                        reject(new Error('Upload thành công nhưng không đọc được ETag. Hãy cài CORS cho bucket và expose header ETag.'));
                        return;
                    }

                    resolve(etag);
                    return;
                }

                reject(new Error('Upload part thất bại với HTTP ' + xhr.status));
            };

            xhr.onerror = function() {
                reject(new Error('Không thể kết nối tới cloud storage. Kiểm tra CORS và kết nối mạng.'));
            };

            xhr.send(blob);
        });
    }

    async function startUpload() {
        var fileInput = $('#khomanguon-r2-file')[0];
        var file = fileInput && fileInput.files ? fileInput.files[0] : null;
        var key = normalizeKey($('#khomanguon-r2-key').val());

        if (!file) {
            setStatus('Vui lòng chọn file cần upload.', true);
            return;
        }

        if (!key) {
            setStatus('Vui lòng nhập object key trên cloud storage.', true);
            return;
        }

        var uploadButton = $('#khomanguon-r2-start-upload');
        var uploadId = '';

        uploadButton.prop('disabled', true);
        setProgress(0);
        setStatus('Đang khởi tạo upload...');

        try {
            var created = await request('khomanguon_r2_create_multipart_upload', {
                key: key,
                content_type: file.type || 'application/octet-stream'
            });

            uploadId = created.uploadId;
            key = normalizeKey(created.key || key);
            $('#khomanguon-r2-key').val(key);

            var totalParts = Math.ceil(file.size / partSize);
            var completedParts = [];

            for (var partNumber = 1; partNumber <= totalParts; partNumber++) {
                var start = (partNumber - 1) * partSize;
                var end = Math.min(start + partSize, file.size);
                var blob = file.slice(start, end);

                setStatus('Đang upload part ' + partNumber + '/' + totalParts + '...');

                var signed = await request('khomanguon_r2_sign_multipart_part', {
                    key: key,
                    upload_id: uploadId,
                    part_number: partNumber
                });

                var etag = await uploadPart(signed.url, blob);
                completedParts.push({
                    PartNumber: partNumber,
                    ETag: etag
                });

                setProgress((partNumber / totalParts) * 100);
            }

            await request('khomanguon_r2_complete_multipart_upload', {
                key: key,
                upload_id: uploadId,
                parts: JSON.stringify(completedParts)
            });

            setStatus('Upload hoàn tất. Object key: ' + key);
            loadFiles();
        } catch (error) {
            setStatus(error.responseJSON && error.responseJSON.message ? error.responseJSON.message : error.message || 'Upload thất bại.', true);

            if (uploadId) {
                request('khomanguon_r2_abort_multipart_upload', {
                    key: key,
                    upload_id: uploadId
                });
            }
        } finally {
            uploadButton.prop('disabled', false);
        }
    }

    function renderFiles(files) {
        var body = $('#khomanguon-r2-files-body');
        body.empty();

        if (!files.length) {
            body.append('<tr><td colspan="9">Không có tệp nào.</td></tr>');
            updateTotals(0, 0, 0);
            return;
        }

        files.forEach(function(file) {
            var row = $('<tr></tr>');
            var key = $('<code></code>').text(file.key);
            var filePath = getFilePath(file.key);
            var members = (file.members || []).map(function(member) {
                return member.name + ' (' + member.downloadCount + ' lần)';
            }).join(', ');
            var displayNameInput = $('<input type="text" class="form-control khomanguon-r2-display-name">')
                .val(file.displayName || file.key)
                .attr('data-key', file.key)
                .attr('data-original-name', file.displayName || file.key);

            row.append($('<td class="khomanguon-r2-name-cell"></td>').append(displayNameInput));
            row.append($('<td class="khomanguon-r2-key-cell"></td>').append(key));
            row.append($('<td class="khomanguon-r2-path-column khomanguon-r2-path-cell"></td>').append($('<code></code>').text(filePath)));
            row.append($('<td></td>').text(formatSize(file.size)));
            row.append($('<td></td>').text(file.lastModified || '-'));
            row.append($('<td></td>').text(Number(file.downloadCount || 0).toLocaleString('vi-VN')));
            row.append($('<td class="khomanguon-r2-members-cell"></td>').text(members || '-'));
            row.append($('<td></td>').text(formatCash(file.revenue)));
            row.append(
                $('<td></td>').append(
                    $('<button type="button" class="button button-small khomanguon-r2-copy-key">Copy key</button>').attr('data-key', file.key),
                    ' ',
                    $('<button type="button" class="button button-small khomanguon-r2-copy-path">Copy path</button>').attr('data-path', filePath),
                    ' ',
                    $('<button type="button" class="button button-small button-link-delete khomanguon-r2-delete-file">Xoá</button>').attr('data-key', file.key)
                )
            );

            body.append(row);
        });

        toggleFilePathColumn();
    }

    function loadFiles(resetToken) {
        if (!getProviderConfig().configured) {
            updateProviderState();
            return;
        }

        if (resetToken) {
            continuationToken = '';
        }

        $('#khomanguon-r2-files-body').html('<tr><td colspan="9">Đang tải danh sách tệp...</td></tr>');

        request('khomanguon_r2_list_files', {
            prefix: normalizeKey($('#khomanguon-r2-list-prefix').val()),
            continuation_token: continuationToken
        }).done(function(response) {
            continuationToken = response.nextContinuationToken || '';
            renderFiles(response.files || []);
            updateTotals(response.totalTrackedFiles || (response.files || []).length, response.totalDownloads || 0, response.totalRevenue || 0);
        }).fail(function(xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể tải danh sách tệp.';
            $('#khomanguon-r2-files-body').html('<tr><td colspan="9">' + message + '</td></tr>');
            updateTotals(0, 0, 0);
        });
    }

    $('#khomanguon-r2-file').on('change', function() {
        var file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) {
            return;
        }

        $('#khomanguon-r2-key').val(joinKey($('#khomanguon-r2-prefix').val(), file.name));
    });

    $('#khomanguon-r2-prefix').on('input', function() {
        var fileInput = $('#khomanguon-r2-file')[0];
        var file = fileInput && fileInput.files ? fileInput.files[0] : null;
        if (file) {
            $('#khomanguon-r2-key').val(joinKey($(this).val(), file.name));
        }
    });

    $('#khomanguon-cloud-provider').on('change', function() {
        updateProviderState();
        var fileInput = $('#khomanguon-r2-file')[0];
        var file = fileInput && fileInput.files ? fileInput.files[0] : null;
        if (file) {
            $('#khomanguon-r2-key').val(joinKey($('#khomanguon-r2-prefix').val(), file.name));
        }
        loadFiles(true);
    });

    $('#khomanguon-r2-start-upload').on('click', function() {
        startUpload();
    });

    $('#khomanguon-r2-refresh').on('click', function() {
        loadFiles(true);
    });

    $('#khomanguon-r2-apply-cors').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);

        request('khomanguon_r2_apply_cors')
            .done(function(response) {
                if (window.Swal) {
                    Swal.fire(response.message);
                } else {
                    alert(response.message);
                }
            })
            .fail(function(xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể cài CORS.';
                if (window.Swal) {
                    Swal.fire(message);
                } else {
                    alert(message);
                }
            })
            .always(function() {
                button.prop('disabled', false);
            });
    });

    $(document).on('click', '.khomanguon-r2-copy-key', function() {
        var key = $(this).data('key');

        copyText(key);
        setStatus('Đã copy key: ' + key);
    });

    $(document).on('click', '.khomanguon-r2-copy-path', function() {
        var filePath = $(this).data('path');

        copyText(filePath);
        setStatus('Đã copy file path: ' + filePath);
    });

    $('#khomanguon-r2-show-file-path').on('change', function() {
        toggleFilePathColumn();
    });

    $(document).on('click', '.khomanguon-r2-delete-file', function() {
        var key = $(this).data('key');
        if (!window.confirm('Xoá tệp này khỏi R2?\n' + key)) {
            return;
        }

        request('khomanguon_r2_delete_file', {
            key: key
        }).done(function() {
            loadFiles(true);
        }).fail(function(xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể xoá tệp.';
            alert(message);
        });
    });

    function saveDisplayName(input) {
        var field = $(input);
        var key = field.data('key');
        var displayName = (field.val() || '').trim();
        var originalName = field.attr('data-original-name') || '';

        if (field.data('saving')) {
            return;
        }

        if (!displayName) {
            field.val(originalName || key);
            setStatus('Tên file không được để trống.', true);
            return;
        }

        if (displayName === originalName) {
            return;
        }

        field.data('saving', true);
        field.prop('disabled', true);
        setStatus('Đang lưu tên file...');

        request('khomanguon_r2_update_file_name', {
            key: key,
            display_name: displayName
        }).done(function(response) {
            field.attr('data-original-name', response.displayName || displayName);
            field.val(response.displayName || displayName);
            setStatus('Đã lưu tên file: ' + (response.displayName || displayName));
        }).fail(function(xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể lưu tên file.';
            field.val(originalName || key);
            setStatus(message, true);
        }).always(function() {
            field.data('saving', false);
            field.prop('disabled', false);
        });
    }

    $(document).on('blur', '.khomanguon-r2-display-name', function() {
        saveDisplayName(this);
    });

    $(document).on('keydown', '.khomanguon-r2-display-name', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            saveDisplayName(this);
            $(this).blur();
        }
    });

    if ($('#khomanguon-r2-files-body').length) {
        updateProviderState();
        loadFiles(true);
    }
});
