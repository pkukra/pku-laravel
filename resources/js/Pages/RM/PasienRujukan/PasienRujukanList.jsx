import React, { useState, useEffect, useRef } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Card, Input, Button, Space, Table, Tooltip } from "antd";
import axios from "axios";
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

export default function PasienRujukanList({ auth }) {
    const [dataSource, setDataSource] = useState([]);
    const [loading, setLoading] = useState(false);
    const [noRm, setNoRm] = useState("");

    const handleInputChange = (e) => {
        setNoRm(e.target.value);
    };

    const handleSearch = async () => {
        if (!noRm) return;

        localStorage.setItem("noRm", noRm);
        fetchData(noRm);
    };

    const handleReset = () => {
        localStorage.removeItem("noRm");
        setNoRm("");
        setDataSource([]);
    };

    const fetchData = async (noRmValue) => {
        setLoading(true);
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.list", { no_rm: noRmValue })
            );
            setDataSource(response?.data?.pasien_rujukans || []);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoading(false);
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
            fetchData(savedNoRm);
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
            <Card title="Pasien Rawat Jalan">
                <Table
                    dataSource={dataSource}
                    columns={columns}
                    size="small"
                    loading={loading}
                    rowKey="FRPNOTRANSAKSIKJ"
                    scroll={{ x: "max-content" }}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
