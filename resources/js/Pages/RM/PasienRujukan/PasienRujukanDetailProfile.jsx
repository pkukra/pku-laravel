import React, { } from "react";
import moment from "moment";

export default function Index({
    pasien,
}) {
    return (
        <>
            <div className="card bg-base-100">
                <div className="card-body">
                    <div className="overflow-x-auto">
                        <table style={{ width: "100%" }}>
                            <tbody>
                                <tr>
                                    <td
                                        style={{
                                            width: "50%",
                                            verticalAlign: "top",
                                        }}
                                    >
                                        <table
                                            className="table table-xs"
                                            style={{ width: "100%" }}
                                        >
                                            <tbody>
                                                <tr>
                                                    <th
                                                        style={{ width: "25%" }}
                                                    >
                                                        Tanggal Periksa
                                                    </th>
                                                    <td>
                                                        {moment(
                                                            pasien.FRPTGL
                                                        ).format(
                                                            "DD/MM/YYYY"
                                                        )}{" "}
                                                        {moment(
                                                            pasien.FRPJAM
                                                        ).format("HH:mm")}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Nomer RM</th>
                                                    <td>
                                                        {pasien.FRPPASIEN_ID}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>
                                                        Nama / Jenis Kelamin
                                                    </th>
                                                    <td>
                                                        {pasien.NAMAPASIEN} /{" "}
                                                        {pasien.JENIS_KELAMIN ==
                                                        "1"
                                                            ? "Laki-laki"
                                                            : "Perempuan"}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>
                                                        Tanggal Lahir (Umur)
                                                    </th>
                                                    <td>
                                                        {moment(
                                                            pasien.TGL_LAHIR
                                                        ).format(
                                                            "DD/MM/YYYY"
                                                        )}{" "}
                                                        &nbsp; (
                                                        {moment().diff(
                                                            moment(
                                                                pasien.TGL_LAHIR
                                                            ),
                                                            "years"
                                                        )}{" "}
                                                        tahun &nbsp;
                                                        {moment().diff(
                                                            moment(
                                                                pasien.TGL_LAHIR
                                                            ),
                                                            "months"
                                                        ) % 12}{" "}
                                                        bulan &nbsp;
                                                        {moment().diff(
                                                            moment(
                                                                pasien.TGL_LAHIR
                                                            ),
                                                            "days"
                                                        ) % 30}{" "}
                                                        hari)
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Alamat</th>
                                                    <td>{pasien.ALAMAT}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>

                                    <td
                                        style={{
                                            width: "50%",
                                            verticalAlign: "top",
                                        }}
                                    >
                                        <table
                                            className="table table-xs"
                                            style={{ width: "100%" }}
                                        >
                                            <tbody>
                                                <tr>
                                                    <th
                                                        style={{ width: "25%" }}
                                                    >
                                                        Unit
                                                    </th>
                                                    <td>
                                                        {pasien.FRPUNIT} -{" "}
                                                        {pasien.FMPKLINIKN}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Golongan Darah</th>
                                                    <td>{pasien.DARAH}</td>
                                                </tr>

                                                <tr>
                                                    <th>Dokter</th>
                                                    <td>
                                                        {pasien.FRPDOKTER_ID} -{" "}
                                                        {pasien.FMDDOKTERN}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Kelompok Pasien</th>
                                                    <td>
                                                        {pasien.FRPCUSTOMER_ID}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Id transakasi</th>
                                                    <td>
                                                        {
                                                            pasien.FRPNOTRANSAKSIKJ
                                                        }
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}
