(function () {
    var csrfToken = $('meta[name="_csrf"]').attr('content');
    var currentPage = 1;
    var currentSearch = '';

    function humanSize(bytes) {
        if (!bytes) return '';
        var units = ['B', 'KB', 'MB', 'GB'];
        var i = 0;
        bytes = parseInt(bytes, 10);
        while (bytes >= 1024 && i < units.length - 1) {
            bytes /= 1024;
            i++;
        }
        return bytes.toFixed(1) + ' ' + units[i];
    }

    function loadMedia(page) {
        currentPage = page || 1;
        $.ajax({
            type: 'GET',
            url: 'api/media',
            data: { action: 'list', page: currentPage, search: currentSearch },
            dataType: 'json',
        }).done(function (data) {
            if (!data.status) return;
            renderGrid(data.items);
            renderPagination(data.pagination);
        });
    }

    function isImageMime(mime) {
        return (mime || '').indexOf('image/') === 0;
    }

    function buildCard(item) {
        var thumb;
        if (isImageMime(item.mime_type)) {
            thumb = '<img src="' + item.url + '" class="card-img-top" style="height:110px;object-fit:cover" alt="' + (item.original_name || '') + '">';
        } else {
            var ext = ((item.original_name || item.filename || '').split('.').pop() || '').toUpperCase();
            thumb =
                '<div class="d-flex flex-column align-items-center justify-content-center" style="height:110px;background:#f5f5f9;">' +
                '<i class="mdi mdi-file-outline" style="font-size:36px;color:#8a8d93;"></i>' +
                '<small class="text-muted mt-1">' + ext + '</small>' +
                '</div>';
        }
        return $(
            '<div class="col-6 col-md-3 col-lg-2" data-media-id="' + item.id + '">' +
            '<div class="card h-100">' +
            thumb +
            '<div class="card-body p-2">' +
            '<p class="mb-1 text-truncate small" title="' + (item.original_name || '') + '">' + (item.original_name || item.filename) + '</p>' +
            '<p class="mb-2 text-muted" style="font-size:11px">' + humanSize(item.size) + '</p>' +
            '<button type="button" class="btn btn-sm btn-outline-danger w-100 media-delete-btn" data-id="' + item.id + '">حذف</button>' +
            '</div></div></div>'
        );
    }

    function renderGrid(items) {
        var $grid = $('#mediaGrid');
        $grid.empty();
        if (!items.length) {
            $grid.html('<div class="col-12 text-center text-muted py-5">لا توجد ملفات في المكتبة بعد</div>');
            return;
        }
        items.forEach(function (item) {
            $grid.append(buildCard(item));
        });
    }

    function prependCard(item) {
        var $grid = $('#mediaGrid');
        // إزالة رسالة "لا توجد ملفات" إن كانت ظاهرة
        if ($grid.children().length === 1 && !$grid.children().first().attr('data-media-id')) {
            $grid.empty();
        }
        $grid.prepend(buildCard(item));
    }

    function renderPagination(pagination) {
        var $p = $('#mediaPagination');
        $p.empty();
        if (!pagination || pagination.total_pages <= 1) return;
        var $nav = $('<nav><ul class="pagination justify-content-center"></ul></nav>');
        var $ul = $nav.find('ul');
        pagination.pages.forEach(function (num) {
            var $li = $('<li class="page-item ' + (num === pagination.page ? 'active' : '') + '"><a href="#" class="page-link">' + num + '</a></li>');
            $li.on('click', function (e) {
                e.preventDefault();
                loadMedia(num);
            });
            $ul.append($li);
        });
        $p.append($nav);
    }

    // رفع ملف واحد مع تتبع التقدم عبر XMLHttpRequest (لعرضه داخل SweetAlert)
    function uploadFileWithProgress(file, onProgress) {
        return new Promise(function (resolve) {
            var formData = new FormData();
            formData.append('action', 'upload');
            formData.append('file', file);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/media', true);
            xhr.setRequestHeader('_csrf', csrfToken);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable) {
                    onProgress(Math.round((e.loaded / e.total) * 100));
                }
            };

            xhr.onload = function () {
                var data;
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (err) {
                    data = { status: false, message: 'استجابة غير صالحة من الخادم' };
                }
                resolve(data);
            };

            xhr.onerror = function () {
                resolve({ status: false, message: 'تعذّر الاتصال بالخادم' });
            };

            xhr.send(formData);
        });
    }

    function updateProgressUI(fileIndex, totalFiles, fileName, percent) {
        var $bar = document.getElementById('mediaUploadProgressBar');
        var $status = document.getElementById('mediaUploadStatus');
        if ($bar) {
            $bar.style.width = percent + '%';
            $bar.textContent = percent + '%';
        }
        if ($status) {
            $status.textContent = 'رفع الملف ' + fileIndex + ' من ' + totalFiles + ': ' + fileName;
        }
    }

    async function uploadFiles(files) {
        var total = files.length;
        var failedMessages = [];

        Swal.fire({
            title: 'جاري رفع الملفات...',
            html:
                '<div class="progress" style="height:22px">' +
                '<div id="mediaUploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>' +
                '</div>' +
                '<p id="mediaUploadStatus" class="mt-2 mb-0 text-muted small"></p>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
        });

        for (var i = 0; i < total; i++) {
            var file = files[i];
            updateProgressUI(i + 1, total, file.name, 0);

            var data = await uploadFileWithProgress(file, function (percent) {
                updateProgressUI(i + 1, total, file.name, percent);
            });

            if (data.status && data.item) {
                prependCard(data.item);
            } else {
                failedMessages.push(file.name + ': ' + (data.message || 'فشل الرفع'));
            }
        }

        Swal.close();

        if (failedMessages.length) {
            Swal.fire({
                icon: 'warning',
                title: 'تم الرفع مع بعض الأخطاء',
                html: failedMessages.map(function (m) { return '<div>' + m + '</div>'; }).join(''),
            });
        } else {
            Swal.fire({
                icon: 'success',
                title: 'تم رفع ' + total + (total === 1 ? ' ملف' : ' ملفات') + ' بنجاح',
                toast: true,
                position: 'top-start',
                showConfirmButton: false,
                timer: 2500,
            });
        }

        // تحديث الترقيم/العدّاد في الخلفية دون التأثير على ما ظهر فوراً
        loadMedia(1);
    }

    $(document).on('change', '#mediaUploadInput', function () {
        var files = this.files;
        if (!files.length) return;
        uploadFiles(Array.prototype.slice.call(files));
        $(this).val('');
    });

    $(document).on('input', '#mediaSearch', function () {
        currentSearch = $(this).val();
        clearTimeout(window.__mediaSearchTimer);
        window.__mediaSearchTimer = setTimeout(function () {
            loadMedia(1);
        }, 400);
    });

    $(document).on('click', '.media-delete-btn', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'هل أنت متأكد من حذف هذا الملف؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'تراجع',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                type: 'POST',
                url: 'api/media',
                headers: { '_csrf': csrfToken },
                data: JSON.stringify({ action: 'delete', id: id }),
                dataType: 'json',
            }).done(function (data) {
                if (data.status) {
                    loadMedia(currentPage);
                } else {
                    Swal.fire({ icon: 'error', title: data.message, toast: true, position: 'top-start', showConfirmButton: false, timer: 3000 });
                }
            });
        });
    });

    if ($('#mediaGrid').length) {
        loadMedia(1);
    }
})();
