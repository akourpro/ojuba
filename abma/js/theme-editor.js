(function () {
    var csrfToken = $('meta[name="_csrf"]').attr('content');
    var currentPath = null;
    var selectedFolder = '';
    var dirty = false;
    var cm = null;

    function apiCall(action, payload) {
        return $.ajax({
            type: 'POST',
            url: 'api/theme-editor',
            headers: { '_csrf': csrfToken },
            data: JSON.stringify(Object.assign({ action: action }, payload || {})),
            contentType: 'application/json',
            dataType: 'json',
        });
    }

    function toast(icon, message) {
        Swal.fire({
            icon: icon,
            title: message,
            toast: true,
            position: 'top-start',
            showConfirmButton: false,
            timer: 2800,
            timerProgressBar: true,
        });
    }

    function modeForExt(ext) {
        switch (ext) {
            case 'twig':
            case 'html':
                return 'htmlmixed';
            case 'css':
                return 'css';
            case 'js':
                return 'javascript';
            case 'json':
                return { name: 'javascript', json: true };
            case 'svg':
                return 'xml';
            default:
                return null;
        }
    }

    // في حال تعذّر تحميل CodeMirror (لا يوجد اتصال بالإنترنت مثلاً)، نستخدم
    // textarea عادية بدون تلوين أكواد بدلاً من كسر المحرر بالكامل
    var codeMirrorAvailable = (typeof window.CodeMirror !== 'undefined');

    function markDirty() {
        if (!dirty) {
            dirty = true;
            $('#teSaveBtn').removeClass('btn-primary').addClass('btn-warning').html('<i class="mdi mdi-content-save-outline"></i> حفظ (تعديلات غير محفوظة)');
        }
    }

    function ensureEditor() {
        if (cm) return cm;
        if (!codeMirrorAvailable) {
            $('#teTextarea').on('input', markDirty);
            cm = {
                setOption: function () {},
                setValue: function (v) { $('#teTextarea').val(v); },
                getValue: function () { return $('#teTextarea').val(); },
                refresh: function () {},
            };
            return cm;
        }
        cm = CodeMirror.fromTextArea(document.getElementById('teTextarea'), {
            lineNumbers: true,
            theme: 'dracula',
            matchBrackets: true,
            tabSize: 4,
            indentUnit: 4,
            lineWrapping: false,
        });
        cm.on('change', markDirty);
        return cm;
    }

    function openFile(path) {
        apiCall('read', { path: path }).done(function (res) {
            if (!res.status) {
                toast('error', res.message);
                return;
            }
            $('#teEmptyState').hide();
            $('#teTextarea').show();
            var editor = ensureEditor();
            var ext = path.split('.').pop().toLowerCase();
            editor.setOption('mode', modeForExt(ext));
            editor.setValue(res.content);
            editor.refresh();
            dirty = false;
            currentPath = path;
            selectedFolder = path.indexOf('/') > -1 ? path.substring(0, path.lastIndexOf('/')) : '';
            $('#teCurrentPath').text(path);
            $('#teSaveBtn, #teDeleteBtn').show();
            $('#teSaveBtn').removeClass('btn-warning').addClass('btn-primary').html('<i class="mdi mdi-content-save-outline"></i> حفظ');
            $('.te-row').removeClass('active');
            $('.te-row[data-path="' + cssEscape(path) + '"]').addClass('active');
        }).fail(function () {
            toast('error', 'تعذّر قراءة الملف');
        });
    }

    function cssEscape(s) {
        return s.replace(/(["\\])/g, '\\$1');
    }

    // Tree interactions
    $(document).on('click', '.te-row', function (e) {
        var $row = $(this);
        var type = $row.data('type');
        var path = $row.data('path');

        if (type === 'folder') {
            $row.parent('.te-folder').toggleClass('open');
            selectedFolder = path;
            return;
        }

        // file
        if (String($row.data('editable')) !== '1') {
            toast('warning', 'هذا النوع من الملفات غير قابل للتحرير من هنا');
            return;
        }
        if (dirty) {
            Swal.fire({
                title: 'لديك تعديلات غير محفوظة، هل تريد المتابعة بدون حفظ؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'متابعة',
                cancelButtonText: 'إلغاء',
            }).then(function (result) {
                if (result.isConfirmed) openFile(path);
            });
        } else {
            openFile(path);
        }
    });

    // Save
    $('#teSaveBtn').on('click', function () {
        if (!currentPath || !cm) return;
        apiCall('save', { path: currentPath, content: cm.getValue() }).done(function (res) {
            if (res.status) {
                dirty = false;
                $('#teSaveBtn').removeClass('btn-warning').addClass('btn-primary').html('<i class="mdi mdi-content-save-outline"></i> حفظ');
                toast('success', res.message);
            } else {
                toast('error', res.message);
            }
        }).fail(function () {
            toast('error', 'تعذّر الحفظ');
        });
    });

    // Delete
    $('#teDeleteBtn').on('click', function () {
        if (!currentPath) return;
        Swal.fire({
            title: 'هل أنت متأكد من حذف (' + currentPath + ')',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#FF0000',
            cancelButtonColor: '#9A9A9A',
            confirmButtonText: 'نعم',
            cancelButtonText: 'تراجع',
        }).then(function (result) {
            if (result.isConfirmed) {
                apiCall('delete', { path: currentPath }).done(function (res) {
                    if (res.status) {
                        toast('success', res.message);
                        setTimeout(function () { location.reload(); }, 600);
                    } else {
                        toast('error', res.message);
                    }
                });
            }
        });
    });

    // New file
    $('#teNewFile').on('click', function () {
        Swal.fire({
            title: 'اسم الملف الجديد',
            input: 'text',
            inputPlaceholder: 'مثال: partials/new-section.twig',
            inputValue: selectedFolder ? selectedFolder + '/' : '',
            showCancelButton: true,
            confirmButtonText: 'إنشاء',
            cancelButtonText: 'إلغاء',
        }).then(function (result) {
            if (result.isConfirmed && result.value) {
                apiCall('create_file', { path: result.value }).done(function (res) {
                    if (res.status) {
                        toast('success', res.message);
                        setTimeout(function () { location.reload(); }, 500);
                    } else {
                        toast('error', res.message);
                    }
                });
            }
        });
    });

    // New folder
    $('#teNewFolder').on('click', function () {
        Swal.fire({
            title: 'اسم المجلد الجديد',
            input: 'text',
            inputPlaceholder: 'مثال: partials',
            inputValue: selectedFolder ? selectedFolder + '/' : '',
            showCancelButton: true,
            confirmButtonText: 'إنشاء',
            cancelButtonText: 'إلغاء',
        }).then(function (result) {
            if (result.isConfirmed && result.value) {
                apiCall('create_folder', { path: result.value }).done(function (res) {
                    if (res.status) {
                        toast('success', res.message);
                        setTimeout(function () { location.reload(); }, 500);
                    } else {
                        toast('error', res.message);
                    }
                });
            }
        });
    });

    // Warn on unload if dirty
    window.addEventListener('beforeunload', function (e) {
        if (dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
