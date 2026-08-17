const listMeta = [
  { col: 'name', kind: 'name' },
  { col: 'email', kind: 'text' },
  { col: 'phone', kind: 'text' },
  { col: 'seen', kind: 'seen' },
  { col: 'message', kind: 'message' },
  { col: 'date', kind: 'date' }
];

let rowsById = {};

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function decodeHtml(value) {
  const textarea = document.createElement('textarea');
  textarea.innerHTML = String(value ?? '');
  return textarea.value;
}

function formatName(row) {
  const name = `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim();
  return name || '-';
}

function renderCell(meta, row) {
  const v = row[meta.col] ?? '';
  if (meta.kind === 'name') {
    return escapeHtml(formatName(row));
  }
  if (meta.kind === 'message') {
    const msg = String(v ?? '');
    const short = msg.length > 80 ? msg.slice(0, 80) + '...' : msg;
    return `<span title="${escapeHtml(msg)}">${escapeHtml(short)}</span>`;
  }
  if (meta.kind === 'seen') {
    const isSeen = String(v) === '1';
    const cls = isSeen ? 'success' : 'warning';
    const label = isSeen ? 'مقروءة' : 'غير مقروءة';
    return `<span class="badge bg-${cls}">${label}</span>`;
  }
  if (meta.kind === 'date') {
    return v ? new Date(String(v).replace(' ', 'T')).toLocaleString() : '-';
  }
  return escapeHtml(v);
}

function loadPage(p = 1) {
  const f = Object.fromEntries(new FormData(document.getElementById('toolbar')).entries());
  f.action = 'list';
  f.page = p;
  $.post('api/contacts', JSON.stringify(f), null, 'json').done(res => {
    if (!res.status) {
      Swal.fire({ icon: 'error', title: res.message || 'فشل التحميل' });
      return;
    }
    rowsById = {};
    const body = $('#rowsBody').empty();
    res.data.forEach(r => {
      rowsById[r.id] = r;
      let tds = `<td>${escapeHtml(r.id)}</td>`;
      listMeta.forEach(m => {
        tds += `<td>${renderCell(m, r)}</td>`;
      });
      const rowClass = String(r.seen) === '1' ? '' : 'table-warning';
      const acts = `
        <div class="dropdown">
          <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">الإجراء</button>
          <div class="dropdown-menu">
            <button type="button" data-id="${escapeHtml(r.id)}" class="dropdown-item text-primary reply"><i class="mdi mdi-send"></i> رد</button>
          </div>
        </div>`;
      body.append(`<tr data-id="${escapeHtml(r.id)}" class="${rowClass}">${tds}<td>${acts}</td></tr>`);
    });

    const total = res.total;
    const per = res.per;
    const page = res.page;
    const pages = Math.max(1, Math.ceil(total / per));
    $('#pagerInfo').text(`إجمالي: ${total} | صفحة ${page} من ${pages}`);
    const ul = $('#pager').empty();
    const add = (lbl, pg, dis = false, act = false) => {
      ul.append(
        `<li class="page-item ${dis ? 'disabled' : ''} ${act ? 'active' : ''}">
          <a class="page-link" href="#" data-pg="${pg}">${lbl}</a>
        </li>`
      );
    };
    add('«', Math.max(1, page - 1), page <= 1);
    for (let i = Math.max(1, page - 2); i <= Math.min(pages, page + 2); i++) {
      add(i, i, false, i === page);
    }
    add('»', Math.min(pages, page + 1), page >= pages);
  });
}

$('#toolbar').on('submit', function (e) {
  e.preventDefault();
  loadPage(1);
});

$('#pager').on('click', 'a', function (e) {
  e.preventDefault();
  const pg = parseInt($(this).data('pg'), 10);
  if (pg) loadPage(pg);
});

$(function () {
  loadPage(1);
});

$(document).on('click', '.reply', function () {
  const id = $(this).data('id');
  const row = rowsById[id];
  if (!row) return;

  $('#replyId').val(id);
  $('#replyName').val(formatName(row));
  $('#replyEmail').val(row.email || '');
  $('#replyPhone').val(row.phone || '');
  $('#replyDate').val(row.date || '');
  $('#replyOriginal').val(decodeHtml(row.message || ''));
  $('#replySubject').val('رد على رسالتك');
  $('#replyBody').val('');

  if (String(row.seen) !== '1') {
    markSeen(id);
  }

  const modalEl = document.getElementById('replyModal');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
});

function markSeen(id) {
  $.ajax({
    type: 'POST',
    url: 'api/contacts',
    headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
    data: JSON.stringify({ action: 'mark_seen', id }),
    dataType: 'json'
  }).done(res => {
    if (res.status) {
      const row = rowsById[id];
      if (!row) return;
      row.seen = 1;
      const tr = $(`#rowsBody tr[data-id="${id}"]`);
      tr.removeClass('table-warning');
      const seenIndex = listMeta.findIndex(m => m.kind === 'seen');
      if (seenIndex >= 0) {
        const td = tr.find('td').eq(1 + seenIndex);
        td.html(renderCell(listMeta[seenIndex], row));
      }
    }
  });
}

$('#replyForm').on('submit', function (e) {
  e.preventDefault();
  const id = $('#replyId').val();
  const subject = $('#replySubject').val().trim();
  const message = $('#replyBody').val().trim();

  if (!subject || !message) {
    Swal.fire({ icon: 'warning', title: 'يرجى تعبئة عنوان الرد ونص الرسالة' });
    return;
  }

  const sendBtn = $('#replySend');
  sendBtn.prop('disabled', true);

  $.ajax({
    type: 'POST',
    url: 'api/contacts',
    headers: { '_csrf': $('meta[name="_csrf"]').attr('content') },
    data: JSON.stringify({ action: 'reply', id, subject, message }),
    dataType: 'json'
  })
    .done(res => {
      if (res.status) {
        Swal.fire({ icon: 'success', title: res.message || 'تم الإرسال' });
        const modalEl = document.getElementById('replyModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.hide();
      } else {
        Swal.fire({ icon: 'error', title: res.message || 'تعذر الإرسال' });
      }
    })
    .fail(() => {
      Swal.fire({ icon: 'error', title: 'حدث خطأ أثناء الإرسال' });
    })
    .always(() => {
      sendBtn.prop('disabled', false);
    });
});
