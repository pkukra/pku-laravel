import React, { useState, useEffect, useRef } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Card, Input, Button, Space, Table, Tooltip } from "antd";
import axios from "axios";
import moment from "moment";

const columnsRujukan = [
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
        render: (_, record) => (
            <>
                {moment(record.FRPTGL).format("DD/MM/YYYY")}{" "}
                {moment(record.FRPJAM).format("HH:mm")}
            </>
        ),
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
        title: "Action",
        dataIndex: "action",
        key: "action",
        render: (_, record) => (
            <a
                href={route("rm.pasien-rujukan.detail", {
                    kode_reg: record.FRPNOTRANSAKSIKJ,
                })}
            >
                <Button type="primary" size="small">
                    Tampilkan
                </Button>
            </a>
        ),
    },
];
const columnsInap = [
    {
        title: "Spesialisasi",
        dataIndex: "FMSPESIALISASIN",
        key: "FMSPESIALISASIN",
        fixed: "left",
    },
    {
        title: "Kelas Kamar",
        dataIndex: "FMKKAMARN",
        key: "FMKKAMARN",
        fixed: "left",
    },
    {
        title: "Kamar",
        dataIndex: "FMKNAMA_KAMAR",
        key: "FMKNAMA_KAMAR",
        fixed: "left",
    },
    {
        title: "Tanggal Masuk",
        dataIndex: "FRPTGL",
        render: (_, record) => (
            <>
            {moment(record?.FRPTGL).format("DD/MM/YYYY")}
            </>
        ),
    },
    {
        title: "Tanggal Keluar",
        dataIndex: "TGL_KELUAR",
        render: (_, record) => (
            <>
            {moment(record?.TGL_KELUAR).format("DD/MM/YYYY")}
            </>
        ),
    },
    {
        title: "Dokter",
        dataIndex: "FMDDOKTERN",
        key: "FMDDOKTERN",
        fixed: "left",
    },
    {
        title: "Kelompok",
        dataIndex: "PRWIKD_CUSTOMER",
        key: "PRWIKD_CUSTOMER",
    },
    {
        title: "No Transaksi",
        dataIndex: "FTNO_TRANSAKSI",
        key: "FTNO_TRANSAKSI",
    },
    {
        title: "Action",
        dataIndex: "action",
        key: "action",
        render: (_, record) => (
            <a
                href={route("rm.pasien-rujukan.detail", {
                    kode_reg: record?.FTNO_TRANSAKSI,
                })}
            >
                <Button type="primary" size="small">
                    Tampilkan
                </Button>
            </a>
        ),
    },
];

export default function PasienRujukanList({ auth }) {
    const [dataPasienRujukans, setDataPasienRujukans] = useState([]);
    const [dataPasienInap, setDataPasienInap] = useState([]);

    const [loadingFetchRujukan, setLoadingFetchRujukan] = useState(false);
    const [loadingFetcInap, setLoadingFetcInap] = useState(false);
    const [noRm, setNoRm] = useState("");

    const handleInputChange = (e) => {
        setNoRm(e.target.value);
    };

    const handleSearch = async () => {
        if (!noRm) return;

        localStorage.setItem("noRm", noRm);
        fetchDataPasienRujukan(noRm);
        fetchDataPasienInap(noRm);
    };

    const handleReset = () => {
        localStorage.removeItem("noRm");
        setNoRm("");
        setDataPasienRujukans([]);
    };

    const fetchDataPasienRujukan = async (noRmValue) => {
        setLoadingFetchRujukan(true);
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.list", { no_rm: noRmValue })
            );
            console.log(response?.data);

            setDataPasienRujukans(response?.data?.pasien_rujukans || []);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoadingFetchRujukan(false);
        }
    };

    const fetchDataPasienInap = async (noRmValue) => {
        setLoadingFetcInap(true);
        try {
            const response = await axios.get(
                route("rm.pasien-inap.list", { no_rm: noRmValue })
            );

            console.log(response?.data);

            setDataPasienInap(response?.data?.pasien_inaps || []);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoadingFetcInap(false);
        }
    };

    const handleKeyEnter = (e) => {
        if (e.key === "Enter") {
            handleSearch();
        }
    };

    const inputRefNoRM = useRef(null);

    useEffect(() => {
        const savedNoRm = localStorage.getItem("noRm");
        if (savedNoRm) {
            setNoRm(savedNoRm);
            fetchDataPasienRujukan(savedNoRm);
            fetchDataPasienInap(savedNoRm);
        }

        const handleKeyDown = (event) => {
            if (event.key === "F1") {
                inputRefNoRM.current?.focus();
            }
        };

        window.addEventListener("keydown", handleKeyDown);
        return () => {
            window.removeEventListener("keydown", handleKeyDown);
        };
    }, []);

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
                    <Tooltip title="Shif+F1 untuk shortcut" placement="topLeft">
                        <Input
                            ref={inputRefNoRM}
                            allowClear
                            autoFocus
                            placeholder="No RM"
                            value={noRm}
                            onChange={handleInputChange}
                            onKeyDown={handleKeyEnter}
                        />
                    </Tooltip>
                    <Button
                        style={{ width: 80 }}
                        onClick={handleSearch}
                        type="primary"
                    >
                        Cari
                    </Button>
                    <Button
                        style={{ width: 80 }}
                        onClick={handleReset}
                        type="default"
                    >
                        Reset
                    </Button>
                </Space>
            </Card>
            <Card title="Pasien Rawat Jalan" style={{ marginBottom: 5 }}>
                <Table
                    dataSource={dataPasienRujukans}
                    columns={columnsRujukan}
                    size="small"
                    loading={loadingFetchRujukan}
                    rowKey="FRPNOTRANSAKSIKJ"
                    scroll={{ x: "max-content" }}
                    pagination={{
                        pageSize: 5,
                    }}
                />
            </Card>
            <Card title="Pasien Rawat Inap">
                <Table
                    dataSource={dataPasienInap}
                    columns={columnsInap}
                    size="small"
                    loading={loadingFetcInap}
                    rowKey="FTNO_TRANSAKSI"
                    scroll={{ x: "max-content" }}
                    pagination={{
                        pageSize: 5,
                    }}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
