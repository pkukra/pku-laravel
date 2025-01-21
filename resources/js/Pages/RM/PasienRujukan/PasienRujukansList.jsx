import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import moment from "moment";

export default function PasienRujukansList({ auth, pasien_rujukans, count }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-xl text-gray-800 leading-tight">
                    List Kunjungan Pasien
                </p>
            }
        >
            <Head title="Pasien Rujukan List" />
            <div className="flex justify-center py-2">
                <div className="overflow-x-auto">
                    <div className="card">
                        <div className="card-body">
                            <div className="form-control">
                                <div className="input-group">
                                    <input
                                        type="text"
                                        placeholder="No RM"
                                        className="input input-bordered input-sm w-full max-w-xs mr-1"
                                    />
                                    <button className="btn btn-sm btn-primary">Cari</button>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                                className="btn btn-primary btn-sm inline-flex"
                                                onClick={() => {
                                                    return document
                                                        .getElementById(
                                                            `my_modal_4${data.pasien_rujukan_id}`
                                                        )
                                                        .showModal();
                                                }}
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
