<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="">Kode Agen : <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="{{ $top }}" id="kode_agen"
                onkeypress="return event.charCode != 32" placeholder="Kode Agen (Max 5 Digit)" maxlength=5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="">Nama Agen: <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nama_agen" placeholder="Nama Agen" maxlength="50">
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label">Branch Office: <span class="text-danger">*</span></label>
        <select id="fk_branch" class="form-control" style="width: 100%" name="fk_branch">
            @if (auth()->user()->fk_branch == 0)
                <option value="0">All</option>
            @endif
            @foreach ($branch as $i)
                <option value="{{ $i->id }}">{{ $i->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="">No Ktp Agen: <span class="text-danger">*</span></label>
            <input type="text" class="form-control" inputmode="numeric" pattern="[0-9]"
                onkeypress="return /[0-9]/i.test(event.key)" onchange="noMinus(this)" id="noktp"
                placeholder="No Ktp" maxlength="16">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="">Alamat Agen: </label>
            <textarea id="alamat" class="form-control" maxlength="200" rows="4 " placeholder="Alamat"></textarea>
        </div>
    </div>
    <div class="col-md-6">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="">No Hp: </label>
                    <input type="text" class="form-control" id="no_hp" placeholder="No Hp" maxlength="13">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="">Agen Aktif? : </label>
                    <select id="is_active" class="form-control select2modal" style="width: 100%">
                        <option value="0">Tidak Aktif</option>
                        <option value="1">Aktif</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="">Nama Bank: </label>
                    <input type="text" class="form-control" id="nama_bank" placeholder="Nama Bank" maxlength="16">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="">No Rek: </label>
                    <input type="text" class="form-control" id="no_rek" placeholder="No Rek" maxlength="16">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="float-end">
    <a class="btn btn-sm btn-outline-warning rounded-pill" onclick="closeModal('ThisModal')">

        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16.8397 20.1642V6.54639" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round"></path>
            <path d="M20.9173 16.0681L16.8395 20.1648L12.7617 16.0681" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M6.91102 3.83276V17.4505" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round"></path>
            <path d="M2.8335 7.92894L6.91127 3.83228L10.9891 7.92894" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
        Cancel
    </a>
    <a class="btn btn-sm btn-outline-success rounded-pill" onclick="pushData()">
        Save
        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M21.4274 2.5783C20.9274 2.0673 20.1874 1.8783 19.4974 2.0783L3.40742 6.7273C2.67942 6.9293 2.16342 7.5063 2.02442 8.2383C1.88242 8.9843 2.37842 9.9323 3.02642 10.3283L8.05742 13.4003C8.57342 13.7163 9.23942 13.6373 9.66642 13.2093L15.4274 7.4483C15.7174 7.1473 16.1974 7.1473 16.4874 7.4483C16.7774 7.7373 16.7774 8.2083 16.4874 8.5083L10.7164 14.2693C10.2884 14.6973 10.2084 15.3613 10.5234 15.8783L13.5974 20.9283C13.9574 21.5273 14.5774 21.8683 15.2574 21.8683C15.3374 21.8683 15.4274 21.8683 15.5074 21.8573C16.2874 21.7583 16.9074 21.2273 17.1374 20.4773L21.9074 4.5083C22.1174 3.8283 21.9274 3.0883 21.4274 2.5783Z"
                fill="currentColor"></path>
            <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd"
                d="M3.01049 16.8079C2.81849 16.8079 2.62649 16.7349 2.48049 16.5879C2.18749 16.2949 2.18749 15.8209 2.48049 15.5279L3.84549 14.1619C4.13849 13.8699 4.61349 13.8699 4.90649 14.1619C5.19849 14.4549 5.19849 14.9299 4.90649 15.2229L3.54049 16.5879C3.39449 16.7349 3.20249 16.8079 3.01049 16.8079ZM6.77169 18.0003C6.57969 18.0003 6.38769 17.9273 6.24169 17.7803C5.94869 17.4873 5.94869 17.0133 6.24169 16.7203L7.60669 15.3543C7.89969 15.0623 8.37469 15.0623 8.66769 15.3543C8.95969 15.6473 8.95969 16.1223 8.66769 16.4153L7.30169 17.7803C7.15569 17.9273 6.96369 18.0003 6.77169 18.0003ZM7.02539 21.5683C7.17139 21.7153 7.36339 21.7883 7.55539 21.7883C7.74739 21.7883 7.93939 21.7153 8.08539 21.5683L9.45139 20.2033C9.74339 19.9103 9.74339 19.4353 9.45139 19.1423C9.15839 18.8503 8.68339 18.8503 8.39039 19.1423L7.02539 20.5083C6.73239 20.8013 6.73239 21.2753 7.02539 21.5683Z"
                fill="currentColor"></path>
        </svg>
    </a>
</div>
<script>
    $('.select2modal').select2({
        dropdownParent: $('#ThisModal')
    });

    function pushData() {
        var kode = $('#kode_agen').val()
        var nama = $('#nama_agen').val()
        var noktp = $('#noktp').val()

        if (!kode || !nama || !noktp) {
            return Toast.fire({
                icon: "warning",
                title: "Silahkan isi data wajib!"
            });
        }
        $.ajax({
            url: "{{ url('agen/saveAgen') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                kode_agen: kode,
                nama: nama,
                noktp: noktp,
                alamat: $('#alamat').val(),
                no_hp: $('#no_hp').val(),
                nama_bank: $('#nama_bank').val(),
                no_rek: $('#no_rek').val(),
                is_active: $('#is_active').val(),
                fk_branch: $('#fk_branch').val(),
            },
            success: function(data) {
                Toast.fire({
                    icon: "success",
                    title: "Data Updated"
                });
                closeModal('ThisModal')
                getList()
            },
            error: function(xhr, status, error) {
                Toast.fire({
                    icon: "error",
                    title: JSON.parse(xhr.responseText).error
                });

            }
        });

    }
</script>
