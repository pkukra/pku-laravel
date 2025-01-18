import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import moment from "moment";

export default function PasienRujukansList({ auth, pasien_rujukans, count }) {
    // Fungsi untuk memformat tanggal dan jam
    const formatDateTime = (date, time) => {
        const formattedDate = new Date(date).toLocaleDateString("en-GB"); // Format dd/mm/yyyy
        const formattedTime = new Date(`1970-01-01T${time}`).toLocaleTimeString(
            "en-GB",
            { hour: "2-digit", minute: "2-digit" }
        ); // Format HH:mm
        return `${formattedDate} ${formattedTime}`;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-xl text-gray-800 leading-tight">
                    Pasien Rujukan List
                </p>
            }
        >
            <Head title="Pasien Rujukan List" />
            <div className="flex justify-center py-2">
                <div className="overflow-x-auto">
                    <table class="table table-md table-pin-rows table-pin-cols">
                        <thead>
                            <tr>
                                <th>Kode Poly</th>
                                <th>Nama Poly</th>
                                <th>Tgl Jam Periksa</th>
                                <th>Kode Dokter</th>
                                <th>Dokter</th>
                                <th>Kelompok</th>
                                <th>No Transaksi</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pasien_rujukans.map((data) => {
                                return (
                                    <tr
                                        className="text-black hover:bg-slate-300"
                                        key={data.student_id}
                                    >
                                        <td>{data.FRPUNIT}</td>
                                        <td>{data.FMPKLINIKN}</td>
                                        <td>
                                            {moment(data.FRPTGL).format(
                                                "DD/MM/YYYY"
                                            )}{" "}
                                            &nbsp;
                                            {moment(
                                                data.FRPJAM,
                                                "HH:mm"
                                            ).format("HH:mm")}
                                        </td>
                                        <td>{data.FRPDOKTER_ID}</td>
                                        <td>{data.FMDDOKTERN}</td>
                                        <td>{data.FRPCUSTOMER_ID}</td>
                                        <td>{data.FRPNOTRANSAKSIKJ}</td>
                                        <td>
                                            <button
                                                onClick={() => {
                                                    return document
                                                        .getElementById(
                                                            `my_modal_4${data.pasien_rujukan_id}`
                                                        )
                                                        .showModal();
                                                }}
                                                className={`inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 focus:bg-blue-800 active:bg-blue-900 border border-transparent rounded-md font-medium text-sm text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150`}
                                            >
                                                Tampilkan
                                            </button>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Kode Poly</th>
                                <th>Nama Poly</th>
                                <th>Tgl Jam Periksa</th>
                                <th>Kode Dokter</th>
                                <th>Dokter</th>
                                <th>Kelompok</th>
                                <th>No Transaksi</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
