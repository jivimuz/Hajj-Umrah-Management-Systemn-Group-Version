<div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">{{ $jamaah->kode_jamaah ?: '-' }} - {{ $jamaah->nama }}</h5>
            <small>{{ $jamaah->no_ktp }} | {{ $jamaah->jenis_ibadah ?: 'Jenis ibadah belum diisi' }}</small>
        </div>
        <span class="badge bg-soft-primary text-primary">Checklist dokumen</span>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th style="width: 5%">No.</th>
                <th>Jenis Dokumen</th>
                <th style="width: 14%">Checklist</th>
                <th>File</th>
                <th style="width: 13%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documentTypes as $index => $type)
                @php($document = $documents->get($type))
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $type }}</td>
                    <td>
                        <form id="document-form-{{ $jamaah->id }}-{{ $index }}" class="document-form"
                            data-type="{{ $type }}">
                            <input type="hidden" name="jamaah_id" value="{{ $jamaah->id }}">
                            <input type="hidden" name="document_type" value="{{ $type }}">
                            <input class="form-check-input" type="checkbox" name="is_checked" value="1"
                                id="document-{{ $jamaah->id }}-{{ $index }}"
                                {{ $document?->is_checked ? 'checked' : '' }}>
                            <label class="form-check-label"
                                for="document-{{ $jamaah->id }}-{{ $index }}">Sudah ada</label>
                        </form>
                    </td>
                    <td>
                        @if ($document?->file_path)
                            <a href="{{ route('jamaah.documents.preview', $document) }}" target="_blank" rel="noopener"
                                class="text-primary d-block mb-1">
                                {{ $document->original_name ?: 'Preview file' }}
                            </a>
                        @else
                            <span class="text-muted d-block mb-1">Belum ada file</span>
                        @endif
                        <input type="file" name="document"
                            form="document-form-{{ $jamaah->id }}-{{ $index }}"
                            class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                    </td>
                    <td>
                        <button type="submit" form="document-form-{{ $jamaah->id }}-{{ $index }}"
                            class="btn btn-sm btn-outline-success rounded-pill">Simpan</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    $('.document-form').on('submit', function(event) {
        event.preventDefault();
        const form = $(this);
        const button = $(`button[form="${form.attr('id')}"]`);
        const formData = new FormData(form[0]);
        button.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: "{{ url('jamaah/dokumen/save') }}",
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: formData,
            contentType: false,
            processData: false,
            success: function() {
                Toast.fire({
                    icon: 'success',
                    title: 'Dokumen berhasil disimpan'
                });
                openDocuments({{ $jamaah->id }});
                getDocumentList();
            },
            error: function(xhr) {
                Toast.fire({
                    icon: 'error',
                    title: xhr.responseJSON?.message || 'Gagal menyimpan dokumen'
                });
            },
            complete: function() {
                button.prop('disabled', false).text('Simpan');
            }
        });
    });
</script>
