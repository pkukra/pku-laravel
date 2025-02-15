import React, { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Card, Input, Button, Space } from "antd";

import { Table } from "antd";
import axios from "axios"; // Import axios untuk mengambil data
import moment from "moment";

const columns = [
    {
        title: "Kode Poly",
        dataIndex: "FRPUNIT",
        key: "FRPUNIT",
        fixed: "left",
    },
    {
        title: "Nama Poly",
        dataIndex: "FMPKLINIKN",
        key: "FMPKLINIKN",
        fixed: "left",
    },
    {
        title: "Tgl Jam Periksa",
        dataIndex: "FRPTGL",
        render: (_, record) => {
            return (
                <>
                    {moment(record.FRPTGL).format("DD/MM/YYYY")}{" "}
                    {moment(record.FRPJAM).format("HH:mm")}
                </>
            );
        },
    },
    {
        title: "Kode Dokter",
        dataIndex: "FRPDOKTER_ID",
        key: "FRPDOKTER_ID",
    },
    {
        title: "Dokter",
        dataIndex: "FMDDOKTERN",
        key: "FMDDOKTERN",
        fixed: "left",
    },
    {
        title: "Kelompok",
        dataIndex: "FRPCUSTOMER_ID",
        key: "FRPCUSTOMER_ID",
    },
    {
        title: "No Transaksi",
        dataIndex: "FRPNOTRANSAKSIKJ",
        key: "FRPNOTRANSAKSIKJ",
    },
    {
        title: "Tgl Jam Periksa",
        dataIndex: "FRPTGL",
        render: (_, record) => {
            return (
                <>
                    {moment(record.FRPTGL).format("DD/MM/YYYY")}{" "}
                    {moment(record.FRPJAM).format("HH:mm")}
                </>
            );
        },
    },
    {
        title: "Action",
        dataIndex: "action",
        key: "action",
        render: (_, record) => {
            return (
                <>
                    <Button type="primary" size="small">
                        <a
                            href={route("rm.pasien-rujukan.detail", {
                                kode_reg: record.FRPNOTRANSAKSIKJ,
                            })}
                        >
                            Tampilkan
                        </a>
                    </Button>
                </>
            );
        },
    },
];

export default function PasienRujukanList({ auth }) {
    const [dataSource, setDataSource] = useState([]); // State untuk data tabel
    const [loading, setLoading] = useState(false); // State untuk menandakan loading
    const [noRm, setNoRm] = useState(""); // State untuk menyimpan No RM dari input

    // Fungsi untuk menghandle perubahan pada input No RM
    const handleInputChange = (e) => {
        setNoRm(e.target.value); // Update state no_rm ketika input berubah
    };

    // Fungsi untuk melakukan pencarian berdasarkan No RM
    const handleSearch = async () => {
        if (!noRm) return; // Jika No RM kosong, jangan lakukan pencarian

        setLoading(true); // Mulai loading sebelum melakukan fetch

        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.list", { no_rm: noRm })
            );
            const data = response?.data?.pasien_rujukans;
            setDataSource(data || []); // Simpan data ke state dataSource
            console.log(data);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoading(false); // Matikan loading setelah fetch selesai
        }
    };

    // Fungsi untuk menangani event keydown pada input No RM
    const handleKeyDown = (e) => {
        if (e.key === "Enter") {
            handleSearch(); // Jika tombol Enter ditekan, jalankan pencarian
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    List Kunjungan Pasien
                </p>
            }
        >
            <Head title="Pasien Rujukan List" />
            <Card style={{ marginBottom: 10 }}>
                <Space direction="horizontal">
                    <Input
                        className="input input-bordered w-full max-w-xs input-sm"
                        placeholder="No RM"
                        value={noRm} // Mengikat input ke state noRm
                        onChange={handleInputChange} // Update state ketika input berubah
                        onKeyDown={handleKeyDown} // Menangani event keydown untuk enter
                    />
                    <Button
                        style={{ width: 80 }}
                        onClick={handleSearch} // Pencarian berdasarkan noRm
                        type="primary"
                    >
                        Cari
                    </Button>
                </Space>
            </Card>
            <Card title="pasien rawat jalan">
                <Table
                    dataSource={dataSource}
                    columns={columns}
                    size="small"
                    loading={loading} // Menampilkan indikator loading saat data sedang diambil
                    rowKey="FRPNOTRANSAKSIKJ" // Pastikan menggunakan properti unik untuk key baris
                    scroll={{
                        x: "max-content",
                    }}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
