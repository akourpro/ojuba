(function () {
  var typeLabels = {
    text: 'نص',
    number: 'رقم',
    email: 'بريد إلكتروني',
    phone: 'رقم جوال',
    date: 'تاريخ'
  };

  var $container = $('#fieldsContainer');
  var rowIndex = 0;

  function addFieldRow(field) {
    field = field || {};
    rowIndex++;
    var idx = rowIndex;
    var options = Object.keys(typeLabels).map(function (t) {
      var sel = (field.type === t) ? 'selected' : '';
      return '<option value="' + t + '" ' + sel + '>' + typeLabels[t] + '</option>';
    }).join('');

    var $row = $(
      '<div class="row g-2 align-items-center mb-2 field-row" data-key="' + (field.key || '') + '">' +
      '<div class="col-md-6">' +
      '<input type="text" class="form-control field-label" placeholder="اسم الحقل (مثال: الشركة)" value="' + (field.label ? field.label.replace(/"/g, '&quot;') : '') + '">' +
      '</div>' +
      '<div class="col-md-4">' +
      '<select class="form-select field-type">' + options + '</select>' +
      '</div>' +
      '<div class="col-md-2">' +
      '<button type="button" class="btn btn-outline-danger w-100 remove-field-btn"><i class="mdi mdi-delete"></i></button>' +
      '</div>' +
      '</div>'
    );
    $container.append($row);
  }

  $(document).on('click', '#addFieldBtn', function () {
    addFieldRow({});
  });

  $(document).on('click', '.remove-field-btn', function () {
    $(this).closest('.field-row').remove();
  });

  function syncFieldsJson() {
    var fields = [];
    $container.find('.field-row').each(function () {
      var label = $(this).find('.field-label').val().trim();
      var type = $(this).find('.field-type').val();
      var key = $(this).data('key') || '';
      if (label === '') return;
      fields.push({ key: key, label: label, type: type });
    });
    $('#fieldsJson').val(JSON.stringify(fields));
  }

  $(document).on('submit', '#listForm', function () {
    syncFieldsJson();
  });

  // تحميل الحقول الموجودة مسبقاً (وضع التعديل) أو البدء بقائمة فارغة (وضع الإضافة)
  if (window.__existingFields && window.__existingFields.length) {
    window.__existingFields.forEach(function (f) {
      addFieldRow(f);
    });
  }
})();
