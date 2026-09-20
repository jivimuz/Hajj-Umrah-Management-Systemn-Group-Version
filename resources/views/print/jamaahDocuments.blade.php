<title>Checklist Dokumen Jamaah</title>
<style>
    @page {
        margin: 24px;
    }

    body {
        font-family: sans-serif;
        font-size: 11px;
        color: #111;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        border: 1px solid #777;
        padding: 7px;
    }

    th {
        background: #1f79e7;
        color: #fff;
        text-align: left;
    }

    .muted {
        color: #777;
    }
</style>

<table style="border: 0; margin-bottom: 18px">
    <tr>
        <td style="border: 0; width: 20%">
            @if ($clogo)
                <img src="{{ public_path($clogo) }}" style="max-width: 110px; max-height: 55px" alt="">
            @endif
        </td>
        <td style="border: 0">
            <strong style="font-size: 18px">{{ $cname }}</strong><br>
            {{ $caddress }}
        </td>
        <td style="border: 0; text-align: right; font-size: 16px"><strong>Checklist Dokumen</strong></td>
    </tr>
</table>

<table style="margin-bottom: 18px">
    <tr>
        <td style="width: 18%"><strong>ID Jamaah</strong></td>
        <td>{{ $data->kode_jamaah ?: '-' }}</td>
        <td style="width: 18%"><strong>Jenis Ibadah</strong></td>
        <td>{{ $data->type }}</td>
    </tr>
    <tr>
        <td><strong>Nama Jamaah</strong></td>
        <td>{{ $data->nama }}</td>
        <td><strong>Paket</strong></td>
        <td>{{ $data->paket }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width: 7%">No.</th>
            <th>Jenis Dokumen</th>
            <th style="width: 20%">Checklist</th>
            <th>File</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($documentTypes as $index => $type)
            @php($document = $documents->get($type))
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $type }}</td>
                <td>{{ $document?->is_checked || $document?->file_path ? 'Lengkap' : 'Belum Lengkap' }}</td>
                <td>{{ $document?->original_name ?: '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p class="muted">Dicetak pada {{ date('d-m-Y H:i') }}</p>
