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
    Input,
    Typography,
    DatePicker,
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

    const initialKode = queryParams.get("kode") || "";

    const [tanggalRange, setTanggalRange] = useState([
        dayjs(initialTanggalAwal),
        dayjs(initialTanggalAkhir),
    ]);

    // <<< gunakan initialKode
    const [kodeFilter, setKodeFilter] = useState(initialKode);

    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(false);

    const disableFutureDate = (current) => {
        return current && current.endOf("day").isAfter(dayjs());
    };

    const handleSearch = async (kode = kodeFilter) => {
        if (!kode.trim()) {
            alert("Silakan isi filter ICD-10 terlebih dahulu.");
            return;
        }

        try {
            setLoading(true);

            const res = await axios.get(
                route("rm_report.by_code_data.index_data"),
                {
                    params: {
                        date_start: tanggalRange[0].format("YYYY-MM-DD"),
                        date_end: tanggalRange[1].format("YYYY-MM-DD"),
                        icd10: kode,
                    },
                }
            );

            setData(res.data.data);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (initialKode) {
            handleSearch(initialKode);
        }
    }, []);

    const columns = [
        {
            title: "Kelompok Umur",
            dataIndex: "umur",
            key: "umur",
        },
        {
            title: "Laki-laki",
            dataIndex: "laki_laki",
            key: "laki_laki",
            align: "center",
        },
        {
            title: "Perempuan",
            dataIndex: "perempuan",
            key: "perempuan",
            align: "center",
        },
        {
            title: "Total",
            dataIndex: "total",
            key: "total",
            align: "center",
        },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<p>Laporan Per Penyakit Show Group Rajal</p>}
        >
            <Head title="Laporan Per Penyakit Show Group" />

            <Card title="Laporan Per Penyakit Show Group">
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={4}>
                        <Typography.Text strong>Tanggal</Typography.Text>

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

                    <Col span={3}>
                        <Typography.Text strong>ICD 10</Typography.Text>

                        <Input
                            allowClear
                            value={kodeFilter}
                            onChange={(e) => setKodeFilter(e.target.value)}
                            placeholder="ICD 10"
                        />
                    </Col>

                    <Col span={2}>
                        <Typography.Text>&nbsp;</Typography.Text>

                        <Button
                            block
                            type="primary"
                            onClick={() => handleSearch()}
                        >
                            Cari
                        </Button>
                    </Col>
                </Row>

                <Table
                    loading={loading}
                    bordered
                    rowKey="umur"
                    pagination={false}
                    dataSource={data}
                    columns={columns}
                    summary={(pageData) => {
                        let laki = 0;
                        let perempuan = 0;
                        let total = 0;

                        pageData.forEach((item) => {
                            laki += item.laki_laki;
                            perempuan += item.perempuan;
                            total += item.total;
                        });

                        return (
                            <Table.Summary.Row>
                                <Table.Summary.Cell>
                                    <b>Total</b>
                                </Table.Summary.Cell>
                                <Table.Summary.Cell align="center">
                                    <b>{laki}</b>
                                </Table.Summary.Cell>
                                <Table.Summary.Cell align="center">
                                    <b>{perempuan}</b>
                                </Table.Summary.Cell>
                                <Table.Summary.Cell align="center">
                                    <b>{total}</b>
                                </Table.Summary.Cell>
                            </Table.Summary.Row>
                        );
                    }}
                />
            </Card>
        </AuthenticatedLayout>
    );
}