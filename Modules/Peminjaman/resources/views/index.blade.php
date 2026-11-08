@extends('layouts.app')

@section('title', 'Peminjaman')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">Data Peminjaman</h4>
            <p class="text-muted mb-0 small">Catat peminjaman buku tanpa refresh halaman (AJAX).</p>
        </div>
        <button type="button" class="btn btn-primary" id="btnTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Peminjaman
        </button>
    </div>

    <div id="alertBox"></div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Peminjam</th>
                        <th>Judul Buku</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th>Berkas</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelPeminjaman">
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal tambah / edit (satu modal dipakai bersama) --}}
    <div class="modal fade" id="peminjamanModal" tabindex="-1" aria-labelledby="peminjamanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="peminjamanForm" class="modal-content" novalidate>
                <input type="hidden" id="peminjamanId">

                <div class="modal-header">
                    <h5 class="modal-title" id="peminjamanModalLabel">Tambah Peminjaman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_peminjam" class="form-label">Nama Peminjam <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_peminjam" name="nama_peminjam">
                        <div class="invalid-feedback" id="error-nama_peminjam"></div>
                    </div>

                    <div class="mb-3">
                        <label for="judul_buku" class="form-label">Judul Buku <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul_buku" name="judul_buku">
                        <div class="invalid-feedback" id="error-judul_buku"></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="tanggal_pinjam" class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_pinjam" name="tanggal_pinjam">
                            <div class="invalid-feedback" id="error-tanggal_pinjam"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_kembali" class="form-label">Tanggal Kembali <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_kembali" name="tanggal_kembali">
                            <div class="invalid-feedback" id="error-tanggal_kembali"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status">
                            <option value="Dipinjam">Dipinjam</option>
                            <option value="Dikembalikan">Dikembalikan</option>
                        </select>
                        <div class="invalid-feedback" id="error-status"></div>
                    </div>

                    <div class="mb-1">
                        <label for="berkas" class="form-label">Bukti Peminjaman <span class="text-danger" id="berkasWajib">*</span></label>
                        <input type="file" class="form-control" id="berkas" name="berkas" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="invalid-feedback" id="error-berkas"></div>
                        <div class="form-text" id="berkasInfo">Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        const baseUrl = "{{ url('peminjaman') }}";
        const dataUrl = "{{ route('peminjaman.data') }}";
        const modalElement = document.getElementById('peminjamanModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const $form = $('#peminjamanForm');

        // Token CSRF dikirim di setiap request AJAX, Accept JSON supaya error validasi = 422 + JSON
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        // Mencegah XSS: teks dari server tidak pernah dianggap HTML oleh browser
        function escapeHtml(value) {
            return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
            });
        }

        function formatTanggal(value) {
            return value ? value.split('-').reverse().join('-') : '-';
        }

        function showAlert(type, message) {
            const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
            const $alert = $(
                '<div class="alert alert-' + type + ' alert-dismissible fade show d-flex align-items-center gap-2" role="alert">' +
                    '<i class="bi ' + icon + '"></i><span>' + escapeHtml(message) + '</span>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>' +
                '</div>'
            );
            $('#alertBox').html($alert);
            setTimeout(function () {
                bootstrap.Alert.getOrCreateInstance($alert[0]).close();
            }, 4000);
        }

        function clearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
        }

        function showErrors(errors) {
            $.each(errors, function (field, messages) {
                $('#' + field).addClass('is-invalid');
                $('#error-' + field).text(messages[0]);
            });
        }

        function renderTable(rows) {
            if (rows.length === 0) {
                $('#tabelPeminjaman').html(
                    '<tr><td colspan="8" class="text-center text-muted py-4">Belum ada data peminjaman.</td></tr>'
                );
                return;
            }

            let html = '';
            rows.forEach(function (row, index) {
                const badge = row.status === 'Dikembalikan' ? 'text-bg-success' : 'text-bg-warning';
                const berkas = row.berkas_url
                    ? '<a href="' + escapeHtml(row.berkas_url) + '" target="_blank" rel="noopener" class="text-decoration-none">' +
                        '<i class="bi bi-paperclip"></i> ' + escapeHtml(row.nama_berkas || 'Lihat berkas') + '</a>'
                    : '<span class="text-muted">-</span>';

                html +=
                    '<tr>' +
                        '<td class="text-muted">' + (index + 1) + '</td>' +
                        '<td class="fw-medium">' + escapeHtml(row.nama_peminjam) + '</td>' +
                        '<td>' + escapeHtml(row.judul_buku) + '</td>' +
                        '<td>' + formatTanggal(row.tanggal_pinjam) + '</td>' +
                        '<td>' + formatTanggal(row.tanggal_kembali) + '</td>' +
                        '<td><span class="badge ' + badge + '">' + escapeHtml(row.status) + '</span></td>' +
                        '<td>' + berkas + '</td>' +
                        '<td class="text-end text-nowrap">' +
                            '<button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="' + row.id + '">' +
                                '<i class="bi bi-pencil"></i> Edit</button> ' +
                            '<button type="button" class="btn btn-sm btn-outline-danger btn-hapus" data-id="' + row.id + '" data-nama="' + escapeHtml(row.nama_peminjam) + '">' +
                                '<i class="bi bi-trash"></i> Hapus</button>' +
                        '</td>' +
                    '</tr>';
            });
            $('#tabelPeminjaman').html(html);
        }

        function loadData() {
            $.get(dataUrl, renderTable).fail(function () {
                $('#tabelPeminjaman').html(
                    '<tr><td colspan="8" class="text-center text-danger py-4">Gagal memuat data.</td></tr>'
                );
            });
        }

        function resetForm() {
            $form[0].reset();
            $('#peminjamanId').val('');
            clearErrors();
        }

        // ---- Tambah ----
        $('#btnTambah').on('click', function () {
            resetForm();
            $('#peminjamanModalLabel').text('Tambah Peminjaman');
            $('#berkasWajib').show();
            $('#berkasInfo').text('Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.');
            modal.show();
        });

        // ---- Edit: ambil data lewat AJAX lalu isi modal ----
        $('#tabelPeminjaman').on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            resetForm();

            $.get(baseUrl + '/' + id + '/edit', function (data) {
                $('#peminjamanId').val(data.id);
                $('#nama_peminjam').val(data.nama_peminjam);
                $('#judul_buku').val(data.judul_buku);
                $('#tanggal_pinjam').val(data.tanggal_pinjam);
                $('#tanggal_kembali').val(data.tanggal_kembali);
                $('#status').val(data.status);

                $('#peminjamanModalLabel').text('Edit Peminjaman');
                $('#berkasWajib').hide();
                $('#berkasInfo').html(
                    'Berkas saat ini: <a href="' + escapeHtml(data.berkas_url) + '" target="_blank" rel="noopener">' +
                    escapeHtml(data.nama_berkas || 'Lihat berkas') + '</a>. Kosongkan jika tidak ingin mengganti.'
                );
                modal.show();
            }).fail(function () {
                showAlert('danger', 'Data tidak dapat dimuat.');
            });
        });

        // ---- Simpan (tambah / ubah) memakai FormData agar file ikut terkirim ----
        $form.on('submit', function (event) {
            event.preventDefault();
            clearErrors();

            const id = $('#peminjamanId').val();
            const formData = new FormData(this);

            if (id) {
                formData.append('_method', 'PUT'); // method spoofing
            }

            $('#btnSimpan').prop('disabled', true);

            $.ajax({
                url: id ? baseUrl + '/' + id : baseUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    modal.hide();
                    showAlert('success', response.success);
                    loadData();
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        showErrors(xhr.responseJSON.errors);
                    } else if (xhr.status === 419) {
                        showAlert('danger', 'Sesi habis (419). Muat ulang halaman lalu coba lagi.');
                    } else if (xhr.status === 403) {
                        showAlert('danger', 'Anda tidak memiliki hak akses (403).');
                    } else {
                        showAlert('danger', 'Terjadi kesalahan pada server.');
                    }
                },
                complete: function () {
                    $('#btnSimpan').prop('disabled', false);
                }
            });
        });

        // ---- Hapus: konfirmasi pakai modal global dari layout ----
        $('#tabelPeminjaman').on('click', '.btn-hapus', function () {
            const id = $(this).data('id');
            const nama = $(this).data('nama');

            confirmDelete('Data peminjaman "' + nama + '" akan dihapus beserta berkasnya.').then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                $.ajax({
                    url: baseUrl + '/' + id,
                    type: 'POST',
                    data: { _method: 'DELETE' },
                    success: function (response) {
                        showAlert('success', response.success);
                        loadData();
                    },
                    error: function () {
                        showAlert('danger', 'Data gagal dihapus.');
                    }
                });
            });
        });

        loadData();
    });
</script>
@endpush