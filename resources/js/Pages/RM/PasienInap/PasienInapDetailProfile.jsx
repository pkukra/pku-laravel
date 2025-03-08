import React from "react";
import { Card } from "antd";
import moment from "moment";

export default function Index({ pasien }) {
    return (
        <>
            <Card title="Profil Pasien">
                <table
                    className="tw-table tw-table-xs"
                    style={{ width: "100%" }}
                >
                    <tbody>
                        <tr>
                            <td
                                style={{
                                    width: "50%",
                                    verticalAlign: "top",
                                }}
                            >
                                <table
                                    className="tw-table-zebra tw-table-xs"
                                    style={{ width: "100%", textAlign: "left" }}
                                >
                                    <tbody>
                                        <tr>
                                            <th
                                                style={{
                                                    width: "30%",
                                                }}
                                            >
                                                Tanggal Periksa
                                            </th>
                                            <td>
                                                {moment(pasien.PRWITGL_MASUK).format(
                                                    "DD/MM/YYYY"
                                                )}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nomer RM</th>
                                            <td>{pasien.PRWIKD_PASIEN}</td>
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
                                            <td
                                                style={{ verticalAlign: "top" }}
                                            >
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
                                            <td
                                                style={{ verticalAlign: "top" }}
                                            >
                                                {pasien.ALAMAT}
                                            </td>
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
                                    className="tw-table-zebra tw-table-xs"
                                    style={{ width: "100%", textAlign: "left" }}
                                >
                                    <tbody>
                                        <tr>
                                            <th style={{ width: "25%" }}>
                                                Spesialisasi
                                            </th>
                                            <td
                                                style={{ verticalAlign: "top" }}
                                            >
                                                {pasien.FMSPESIALISASIN}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Golongan Darah</th>
                                            <td>{pasien.DARAH}</td>
                                        </tr>

                                        <tr>
                                            <th>Dokter</th>
                                            <td
                                                style={{ verticalAlign: "top" }}
                                            >
                                                {pasien.PRWIKD_DOKTER} -{" "}
                                                {pasien.FMDDOKTERN}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Kelompok Pasien</th>
                                            <td>{pasien.PRWIKD_CUSTOMER}</td>
                                        </tr>
                                        <tr>
                                            <th>ID Transakasi</th>
                                            <td>{pasien.PRWINO_TRANSAKSI}</td>
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
