import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import axios from "axios";
import dayjs from "dayjs";

import {
    Card,
    Button,
    Table,
    Row,
    Col,
    Typography,
    DatePicker,
    message,
} from "antd";

const { RangePicker } = DatePicker;

export default function Index({ auth }) {
    const queryParams = new URLSearchParams(window.location.search);

    const initialTanggalAwal =
        queryParams.get("tanggal_awal") ||
        dayjs().subtract(6, "day").format("YYYY-MM-DD");

    const initialTanggalAkhir =
        queryParams.get("tanggal_akhir") ||
        dayjs().format("YYYY-MM-DD");

    const [tanggalRange, setTanggalRange] = useState([
        dayjs(initialTanggalAwal),
        dayjs(initialTanggalAkhir),
    ]);

    const [loading, setLoading] = useState(false);
    const [data, setData] = useState([]);

    const disableFutureDate = (current) => {
        return current && current.endOf("day").isAfter(dayjs());
    };

    const handleSearch = async () => {
        const tanggalAwal = tanggalRange[0].format("YYYY-MM-DD");
        const tanggalAkhir = tanggalRange[1].format("YYYY-MM-DD");

        // Update query parameter di URL
        const url = new URL(window.location.href);
        url.searchParams.set("tanggal_awal", tanggalAwal);
        url.searchParams.set("tanggal_akhir", tanggalAkhir);
        window.history.replaceState({}, "", url.toString());

        try {
            setLoading(true);

            const res = await axios.get(
                route("rm_report.all_penyakit_index_data"),
                {
                    params: {
                        date_start: tanggalAwal,
                        date_end: tanggalAkhir,
                    },
                }
            );

            setData(res.data.data);
        } catch (e) {
            console.error(e);
            message.error("Gagal mengambil data.");
        } finally {
            setLoading(false);
        }
    };

    const columns = [
        {
            title: "Kode Penyakit",
            dataIndex: "code",
            key: "code",
            width: 180,
            render: (code) => (
                <a
                    href={
                        route("rm_report.by_code.index") +
                        `?tanggal_awal=${tanggalRange[0].format("YYYY-MM-DD")}` +
                        `&tanggal_akhir=${tanggalRange[1].format("YYYY-MM-DD")}` +
                        `&kode=${encodeURIComponent(code)}`
                    }
                >
                    {code}
                </a>
            ),
        },
        {
            title: "Jumlah Pasien",
            dataIndex: "jumlah_pasien",
            key: "jumlah_pasien",
            width: 150,
            align: "right",
            render: (value) => value.toLocaleString(),
        },
    ];

    useEffect(() => {
        if (
            queryParams.get("tanggal_awal") &&
            queryParams.get("tanggal_akhir")
        ) {
            handleSearch();
        }
    }, []);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<p>Daftar Penyakit</p>}
        >
            <Head title="Daftar Penyakit" />

            <Card title="Daftar Penyakit Berdasarkan Periode Ranap">
                <Row gutter={16} style={{ marginBottom: 16 }}>
                    <Col span={5}>
                        <Typography.Text strong>
                            Periode
                        </Typography.Text>

                        <RangePicker
                            style={{ width: "100%" }}
                            value={tanggalRange}
                            format="YYYY-MM-DD"
                            disabledDate={disableFutureDate}
                            onChange={(dates) => {
                                if (!dates) {
                                    setTanggalRange([
                                        dayjs().subtract(6, "day"),
                                        dayjs(),
                                    ]);
                                    return;
                                }

                                setTanggalRange(dates);
                            }}
                        />
                    </Col>

                    <Col span={2}>
                        <Typography.Text>&nbsp;</Typography.Text>

                        <Button
                            block
                            type="primary"
                            onClick={handleSearch}
                        >
                            Cari
                        </Button>
                    </Col>
                </Row>

                <div style={{ marginBottom: 10 }}>
                    Total Penyakit : <b>{data.length}</b>
                </div>

                <Table
                    bordered
                    loading={loading}
                    rowKey="code"
                    columns={columns}
                    dataSource={data}
                    pagination={false}
                />
            </Card>
        </AuthenticatedLayout>
    );
}