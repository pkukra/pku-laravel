import React from "react";
import { Card, Input, Button, Space } from "antd";
import moment from "moment";

export default function Index({ pasien }) {
    return (
        <>
            <Card title="Profil Pasien" style={{ marginBottom: 10 }}>
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
                                    style={{ width: "100%", textAlign: "left" }}
                                >
                                    <tbody>
                                        <tr>
                                            <th
                                                style={{
                                                    width: "30%"
                                                }}
                                            >
                                                Tanggal Periksa
                                            </th>
                                            <td>
                                                {moment(pasien.FRPTGL).format(
                                                    "DD/MM/YYYY"
                                                )}{" "}
                                                {moment(pasien.FRPJAM).format(
                                                    "HH:mm"
                                                )}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nomer RM</th>
                                            <td>{pasien.FRPPASIEN_ID}</td>
                                        </tr>
                                        <tr>
                                            <th>Nama / Jenis Kelamin</th>
                                            <td>
                                                {pasien.NAMAPASIEN} /{" "}
                                                {pasien.JENIS_KELAMIN == "1"
                                                    ? "Laki-laki"
                                                    : "Perempuan"}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Lahir (Umur)</th>
                                            <td style={{verticalAlign:"top"}}>
                                                {moment(
                                                    pasien.TGL_LAHIR
                                                ).format("DD/MM/YYYY")}{" "}
                                                &nbsp; (
                                                {moment().diff(
                                                    moment(pasien.TGL_LAHIR),
                                                    "years"
                                                )}{" "}
                                                tahun &nbsp;
                                                {moment().diff(
                                                    moment(pasien.TGL_LAHIR),
                                                    "months"
                                                ) % 12}{" "}
                                                bulan &nbsp;
                                                {moment().diff(
                                                    moment(pasien.TGL_LAHIR),
                                                    "days"
                                                ) % 30}{" "}
                                                hari)
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Alamat</th>
                                            <td style={{verticalAlign:"top"}}>{pasien.ALAMAT}</td>
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
                                    style={{ width: "100%", textAlign: "left" }}
                                >
                                    <tbody>
                                        <tr>
                                            <th style={{ width: "25%" }}>
                                                Unit
                                            </th>
                                            <td style={{verticalAlign:"top"}}>
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
                                            <td style={{verticalAlign:"top"}}>
                                                {pasien.FRPDOKTER_ID} -{" "}
                                                {pasien.FMDDOKTERN}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Kelompok Pasien</th>
                                            <td>{pasien.FRPCUSTOMER_ID}</td>
                                        </tr>
                                        <tr>
                                            <th>ID Transakasi</th>
                                            <td>{pasien.FRPNOTRANSAKSIKJ}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </>
    );
}
