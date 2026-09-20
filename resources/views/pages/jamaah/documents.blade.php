@extends('layout/main')

@section('style')
    <style>
        #document-table th,
        #document-table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .document-status {
            display: inline-flex;
            min-width: 28px;
            justify-content: center;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    <div class="content-inner mt-5 py-0">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1">Checklist & Upload Dokumen Jamaah</h4>
                            <small>Daftar kelengkapan dokumen persyaratan jamaah</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="branch_id">Office</label>
                                <select id="branch_id" class="form-control" onchange="getDocumentList()">
                                    @if (auth()->user()->fk_branch == 0)
                                        <option value="0" selected>All</option>
                                    @endif
                                    @foreach ($branch as $item)
                                        <option value="{{ $item->id }}"
                                            {{ $item->id == auth()->user()->fk_branch ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="type">Jenis Ibadah</label>
                                <select id="type" class="form-control" onchange="getDocumentList()">
                                    <option value="">Semua</option>
                                    <option value="Umrah">Umrah</option>
                                    <option value="Haji">Haji</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped" id="document-table" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Jamaah</th>
                                        <th>Nama Jamaah</th>
                                        <th>Paspor</th>
                                        <th>KTP</th>
                                        <th>KK</th>
                                        <th>Buku Nikah</th>
                                        <th>Akte Kelahiran</th>
                                        <th>Foto</th>
                                        <th>Vaksin/Kesehatan</th>
                                        <th>Sertifikat Vaksin Meningitis</th>
                                        <th>Surat Keterangan Sehat</th>
                                        <th>Bukti Pendaftaran</th>
                                        <th>Dokumen Haji</th>
                                        <th>Bukti Setoran Awal</th>
                                        <th>Nomor Porsi</th>
                                        <th>Bukti Pelunasan Haji</th>
                                        <th>Dokumen Tambahan</th>
                                        <th>Status Kelengkapan</th>
                                        <th>Link/Path File</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('component/modal-full')
@endsection

@section('script')
    <script>
        const documentTypes = ['Paspor', 'KTP', 'KK', 'Buku Nikah', 'Akte Kelahiran', 'Foto', 'Vaksin/Kesehatan',
            'Sertifikat Vaksin Meningitis', 'Surat Keterangan Sehat', 'Bukti Pendaftaran', 'Dokumen Haji',
            'Bukti Setoran Awal', 'Nomor Porsi', 'Bukti Pelunasan Haji', 'Dokumen Tambahan'
        ];

        function documentCell(row, type) {
            const document = row.documents[type] || {};
            if (document.applicable === false) {
                return '<span class="document-status bg-soft-secondary text-muted" title="Tidak berlaku untuk jenis ibadah ini">N/A</span>';
            }
            let content = document.checked ?
                '<span class="document-status bg-soft-success text-success" title="Sudah checklist">Ya</span>' :
                '<span class="document-status bg-soft-warning text-warning" title="Belum checklist">-</span>';
            if (document.file) {
                content +=
                    ` <a href="${document.preview}" target="_blank" rel="noopener" class="ms-1 text-primary" title="Preview ${document.name || type}">Preview</a>`;
            }
            return content;
        }

        function fileLinks(row) {
            const links = documentTypes.map(type => {
                const document = row.documents[type] || {};
                return document.file ? `<a href="${document.preview}" target="_blank" rel="noopener">${type}</a>` :
                    '';
            }).filter(Boolean);

            const linkList = links.length ? links.join('<br>') : '<span class="text-muted">Belum ada file</span>';
            return `${linkList}<br><button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-2" onclick="openDocuments(${row.id})">Kelola checklist</button>`;
        }

        function getDocumentList() {
            let rowNumber = 1;
            const columns = [{
                    data: 'id',
                    render: () => `${rowNumber++}.`,
                    className: 'text-center'
                },
                {
                    data: 'kode_jamaah',
                    defaultContent: '-'
                },
                {
                    data: 'nama'
                },
                ...documentTypes.map(type => ({
                    data: 'documents',
                    render: (data, display, row) => documentCell(row, type),
                    className: 'text-center'
                })),
                {
                    data: 'status',
                    render: (data, display, row) => row.status === 'Lengkap' ?
                        `<span class="badge bg-success">${row.completed}/${row.total_documents} Lengkap</span>` :
                        `<span class="badge bg-warning">${row.completed}/${row.total_documents} Belum Lengkap</span>`,
                    className: 'text-center'
                },
                {
                    data: 'id',
                    render: (data, display, row) => fileLinks(row),
                    className: 'text-center'
                }
            ];

            $('#document-table').DataTable({
                destroy: true,
                responsive: true,
                searching: true,
                lengthChange: true,
                ajax: {
                    url: "{{ url('jamaah/dokumen/getList') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: {
                        branch_id: $('#branch_id').val(),
                        type: $('#type').val()
                    }
                },
                columns: columns,
                order: []
            });
        }

        function openDocuments(id) {
            $.ajax({
                url: "{{ url('jamaah/dokumen/form') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    id: id
                },
                success: function(html) {
                    $('#ThisModalLabel').text('Dokumen Jamaah');
                    $('#thisModalBody').html(html);
                    $('#ThisModal').modal('show');
                },
                error: function(xhr) {
                    Toast.fire({
                        icon: 'error',
                        title: xhr.responseJSON?.message || 'Gagal memuat dokumen'
                    });
                }
            });
        }

        $(document).ready(function() {
            getDocumentList();
        });
    </script>
@endsection
