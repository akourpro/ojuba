(function () {
    var modalId = 'mediaPickerModal';
    var currentTarget = null;
    var currentPreview = null;
    var currentPage = 1;

    function ensureModal() {
        if (document.getElementById(modalId)) return;
        var html =
            '<div class="modal fade" id="' + modalId + '" tabindex="-1">' +
            '<div class="modal-dialog modal-lg modal-dialog-scrollable">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h5 class="modal-title">اختر ملفاً من مكتبة الوسائط</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
            '</div>' +
            '<div class="modal-body">' +
            '<input type="text" id="mpSearch" class="form-control mb-3" placeholder="بحث...">' +
            '<div id="mpGrid" class="row g-2"></div>' +
            '<div id="mpPagination" class="mt-3"></div>' +
            '</div>' +
            '</div></div></div>';
        $('body').append(html);
    }

    var currentExt = '';

    function loadList(page, search) {
        currentPage = page || 1;
        $.ajax({
            type: 'GET',
            url: 'api/media',
            data: { action: 'list', page: currentPage, search: search || '', ext: currentExt || '' },
            dataType: 'json',
        }).done(function (data) {
            if (!data.status) return;
            renderGrid(data.items);
            renderPagination(data.pagination, search);
        });
    }

    function isImageMime(mime) {
        return (mime || '').indexOf('image/') === 0;
    }

    function escapeHtml(str) {
        return $('<div>').text(str || '').html();
    }

    function buildTile(item) {
        var displayName = item.original_name || item.filename || '';
        var thumbHtml;
        if (isImageMime(item.mime_type)) {
            thumbHtml = '<img src="' + item.url + '" class="img-fluid rounded" style="height:90px;width:100%;object-fit:cover">';
        } else {
            var ext = (displayName.split('.').pop() || '').toUpperCase();
            thumbHtml =
                '<div class="d-flex flex-column align-items-center justify-content-center" style="height:90px;width:100%;background:#f5f5f9;border-radius:6px;">' +
                '<i class="mdi mdi-file-outline" style="font-size:28px;color:#8a8d93;"></i>' +
                '<small class="text-muted mt-1" style="font-size:10px;">' + ext + '</small>' +
                '</div>';
        }
        return (
            '<div class="mp-select-img" style="cursor:pointer;" ' +
            'data-url="' + item.url + '" data-name="' + (item.filename || '') + '" data-original="' + (item.original_name || '') + '" data-mime="' + (item.mime_type || '') + '">' +
            thumbHtml +
            '<div class="text-truncate small mt-1" title="' + escapeHtml(displayName) + '">' + escapeHtml(displayName) + '</div>' +
            '</div>'
        );
    }

    function renderGrid(items) {
        var $grid = $('#mpGrid');
        $grid.empty();
        if (!items.length) {
            $grid.html('<div class="col-12 text-center text-muted py-4">لا توجد ملفات</div>');
            return;
        }
        items.forEach(function (item) {
            var $col = $('<div class="col-4 col-md-3"></div>');
            $col.html(buildTile(item));
            $grid.append($col);
        });
    }

    function renderPagination(pagination, search) {
        var $p = $('#mpPagination');
        $p.empty();
        if (!pagination || pagination.total_pages <= 1) return;
        var $nav = $('<nav><ul class="pagination pagination-sm justify-content-center"></ul></nav>');
        var $ul = $nav.find('ul');
        pagination.pages.forEach(function (num) {
            var $li = $('<li class="page-item ' + (num === pagination.page ? 'active' : '') + '"><a href="#" class="page-link">' + num + '</a></li>');
            $li.on('click', function (e) {
                e.preventDefault();
                loadList(num, search);
            });
            $ul.append($li);
        });
        $p.append($nav);
    }

    var currentMode = 'inject'; // 'inject' (يحقن الملف داخل input[type=file] الموجود) أو 'reference' (يكتفي بمرجع URL/اسم دون إعادة رفع)
    var currentTriggerBtn = null;

    async function selectImage(url, filename, mime, originalName) {
        if (currentMode === 'reference') {
            // وضع "مرجع فقط": لا نعيد رفع الملف، فقط نبلّغ الصفحة المستدعية بالملف المختار من المكتبة
            $(document).trigger('media-picker:selected', [{
                url: url,
                filename: filename,
                original_name: originalName || filename,
                mime: mime,
                trigger: currentTriggerBtn
            }]);
            $('#' + modalId).modal('hide');
            return;
        }
        try {
            var response = await fetch(url);
            var blob = await response.blob();
            var file = new File([blob], filename || 'file', { type: mime || blob.type });
            var dt = new DataTransfer();
            dt.items.add(file);
            if (currentTarget && currentTarget.length) {
                currentTarget[0].files = dt.files;
                currentTarget.trigger('change');
            }
            if (currentPreview && currentPreview.length) {
                if (currentPreview.is('img')) {
                    currentPreview.attr('src', url);
                } else {
                    currentPreview.css('background-image', 'url(' + url + ')');
                }
            }
            $('#' + modalId).modal('hide');
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'تعذّر اختيار الملف', toast: true, position: 'top-start', showConfirmButton: false, timer: 3000 });
        }
    }

    $(document).on('click', '.media-picker-btn', function (e) {
        e.preventDefault();
        ensureModal();
        currentTriggerBtn = this;
        currentTarget = $($(this).data('target'));
        var previewSel = $(this).data('preview');
        currentPreview = previewSel ? $(previewSel) : null;
        currentExt = $(this).data('ext') || '';
        currentMode = $(this).data('mode') || 'inject';
        var title = $(this).data('title');
        $('#' + modalId + ' .modal-title').text(title || 'اختر ملفاً من مكتبة الوسائط');
        loadList(1, '');
        $('#mpSearch').val('');
        $('#' + modalId).modal('show');
    });

    $(document).on('click', '.mp-select-img', function () {
        selectImage($(this).data('url'), $(this).data('name'), $(this).data('mime'), $(this).data('original'));
    });

    $(document).on('input', '#mpSearch', function () {
        var val = $(this).val();
        clearTimeout(window.__mpSearchTimer);
        window.__mpSearchTimer = setTimeout(function () {
            loadList(1, val);
        }, 400);
    });
})();
