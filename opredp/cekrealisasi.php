<div class="data-panel" id="dataPanel">

    <header>
        <h1>Data SLP Belum Terealisasi</h1>
        <button id="refreshBtn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor">
                <path d="M463.5 224H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 .5C73.2 122 48 160.7 48 204V308c0 13.3 10.7 24 24 24h16c13.3 0 24-10.7 24-24V204c0-34.7 19.3-65.9 48.5-84.8C199.1 82.5 312.9 82.5 345.5 115.2l38.2-38.2c6.9-6.9 17.2-8.9 26.2-5.2S432 82.3 432 92v108c0 13.3 10.7 24 24 24h16c13.3 0 24-10.7 24-24v-8zM72 288h-8c-13.3 0-24 10.7-24 24V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-.5c24.4-24.4 40.2-55.1 44.9-88.7H432c-13.3 0-24-10.7-24-24v-16c0-13.3 10.7-24 24-24h32.5c4.6 33.6 19.9 64.3 44.9 88.7C538.8 389.9 425.1 390 392.5 457.2l-38.2 38.2c-6.9 6.9-17.2 8.9-26.2 5.2S308 489.7 308 480V372c0-13.3-10.7-24-24-24h-16c-13.3 0-24 10.7-24 24v108c0 9.7-5.8 18.5-14.8 22.2s-19.3 1.7-26.2-5.2L161.4 415.4c-87.6-86.5-228.7-86.2-315.8 .5C-20.1 389.9-45.3 351.3-45.3 308V204c0-13.3 10.7-24 24-24h16c13.3 0 24 10.7 24 24v104c0 34.7-19.3 65.9-48.5 84.8z" />
            </svg>
            <span>Refresh Data</span>
        </button>
    </header>

    <div id="statusArea">
        <div class="spinner" id="loadingSpinner"></div>
        <p id="statusMessage">Memuat data...</p>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Slp Id</th>
                    <th>Alamat</th>
                    <th>PLU</th>
                    <th>Deskripsi</th>
                    <th>Qty Ctn</th>
                    <th>Qty Pcs</th>
                    <th>Unit</th>
                    <th>ED</th>
                    <th>ID User</th>
                </tr>
            </thead>
            <tbody id="dataTableBody">
            </tbody>
        </table>
    </div>

</div>


<script src="opredp/scriptcekrealisasi.js"></script>