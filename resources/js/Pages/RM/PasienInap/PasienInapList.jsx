import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Card, Button, Table, Row, Col, DatePicker, Input, Select, Typography } from "antd";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";

export default function Index({ auth, bangsal }) {
    const queryParams = new URLSearchParams(window.location.search);

    const initialPage = parseInt(queryParams.get("page")) || 1;
    const initialPerPage = parseInt(queryParams.get("per_page")) || 100;
    const initialTanggalKeluar =
        queryParams.get("tanggal_keluar") || moment().format("YYYY-MM-DD");
    const initialKodeDokter = queryParams.get("kode_dokter") || "";
    const initialNoRM = queryParams.get("no_rm") || "";
    const initialKodeBangsal = queryParams.get("kode_bangsal") || "";

    const [loading, setLoading] = useState(false);
    const [dataPasienInaps, setDataPasienInaps] = useState([]);
    const [totalData, setTotalData] = useState(0);
    const [page, setPage] = useState(initialPage);
    const [perPage, setPerPage] = useState(initialPerPage);
    const [tanggalKeluar, setTanggalKeluar] = useState(initialTanggalKeluar);
    const [kodeDokter, setKodeDokter] = useState(initialKodeDokter);
    const [noRM, setNoRM] = useState(initialNoRM);
    const [kodeBangsal, setKodeBangsal] = useState(initialKodeBangsal);
    const [isInacbgFinal, setIsInacbgFinal] = useState("");

    const columnsInap = [
        {
            title: "Tanggal Masuk",
            dataIndex: "FTTGL_TRANSAKSI",
            render: (_, record) => (
                <>{moment(record?.FTTGL_TRANSAKSI).format("DD/MM/YYYY")}</>
            ),
        },
        {
            title: "Tanggal Keluar",
            dataIndex: "TGL_KELUAR",
            render: (_, record) => (
                <>
                    {record?.TGL_KELUAR &&
                        moment(record?.TGL_KELUAR).format("DD/MM/YYYY")}
                </>
            ),
        },
        {
            title: "No RM / Nama Pasien",
            dataIndex: "NAMAPASIEN",
            render: (_, record) => (
                <>
                    {record.FTKD_PASIEN} /<br />
                    {record.NAMAPASIEN}
                </>
            ),
        },
        {
            title: "Kamar",
            dataIndex: "FMKNAMA_KAMAR",
            key: "FMKNAMA_KAMAR",
            fixed: "left",
        },
        {
            title: "Dokter",
            dataIndex: "FMDDOKTERN",
            key: "FMDDOKTERN",
            fixed: "left",
            render: (_, record) => (
                <>
                    {record?.PRWIKD_DOKTER} - {record?.FMDDOKTERN}
                </>
            ),
        },
        {
            title: "Kelompok",
            dataIndex: "PRWIKD_CUSTOMER",
            key: "PRWIKD_CUSTOMER",
        },
        {
            title: "Action",
            dataIndex: "action",
            key: "action",
            render: (_, record) => (
                <a
                    href={route("rm.pasien-inap.detail", {
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

    const fetchDataPasienInap = async (
        pageVal = page,
        perPageVal = perPage
    ) => {
        setLoading(true);
        try {
            const response = await axios.get(
                route("rm.pasien-inap.list_inap_data"),
                {
                    params: {
                        tanggal_keluar: tanggalKeluar,
                        page: pageVal,
                        per_page: perPageVal,
                        kode_dokter: kodeDokter,
                        no_rm: noRM,
                        kode_bangsal: kodeBangsal,
                        is_inacbg_final: isInacbgFinal,
                    },
                }
            );

            setDataPasienInaps(response?.data?.data?.data || []);
            setTotalData(response?.data?.data?.total || 0);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoading(false);
        }
    };

    const handleTableChange = (pagination) => {
        const newPage = pagination.current;
        const newPerPage = pagination.pageSize;

        const params = new URLSearchParams(window.location.search);
        params.set("page", newPage);
        params.set("per_page", newPerPage);
        params.set("tanggal_keluar", tanggalKeluar);
        params.set("kode_dokter", kodeDokter);
        params.set("no_rm", noRM);
        window.history.replaceState(null, "", `?${params.toString()}`);

        setPage(newPage);
        setPerPage(newPerPage);
        fetchDataPasienInap(newPage, newPerPage);
    };

    useEffect(() => {
        fetchDataPasienInap();
    }, []);

    const optionsBangsal = [
        ...bangsal.map((item) => ({
            value: item.FMKAMAR_ID,
            label: item.FMKAMARN,
        })),
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    List Kunjungan Pasien Inap
                </p>
            }
        >
            <Head title="List Kunjungan Pasien Inap" />
            <Card title="Pasien Rawat Inap" style={{ marginBottom: 5 }}>
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={3}>
                        <div>
                            <Typography.Text strong>
                                Tanggal Keluar
                            </Typography.Text>
                        </div>
                        <DatePicker
                            allowClear={false}
                            value={dayjs(tanggalKeluar)}
                            onChange={(dateMoment, dateString) =>
                                setTanggalKeluar(dateString)
                            }
                            placeholder="Tanggal Keluar"
                            disabledDate={(current) =>
                                current && current > moment().endOf("day")
                            }
                        />
                    </Col>
                    <Col span={3}>
                        <div>
                            <Typography.Text strong>No RM</Typography.Text>
                        </div>
                        <Input
                            allowClear
                            placeholder="No RM"
                            value={noRM}
                            onChange={(e) => setNoRM(e.target.value)}
                        />
                    </Col>
                    <Col span={4}>
                        <div>
                            <Typography.Text strong>Bangsal</Typography.Text>
                        </div>
                        <Select
                            style={{ width: "100%" }}
                            options={optionsBangsal} // Menampilkan bangsal sebagai opsi
                            value={kodeBangsal} // Nilai yang dipilih
                            onChange={(value) => setKodeBangsal(value)} // Perbarui state ketika ada perubahan
                            allowClear
                            placeholder="Pilih Bangsal"
                        />
                    </Col>
                    <Col span={3}>
                        <div>
                            <Typography.Text strong>
                                Kode Dokter
                            </Typography.Text>
                        </div>
                        <Input
                            allowClear
                            placeholder="Kode Dokter"
                            value={kodeDokter}
                            onChange={(e) => setKodeDokter(e.target.value)}
                        />
                    </Col>

                    <Col span={4}>
                        <div>
                            <Typography.Text strong>
                                Filter Final INACBG
                            </Typography.Text>
                        </div>
                        <Select
                            style={{ width: "100%" }}
                            value={isInacbgFinal} // Menyimpan nilai filter FTKODEINACBG
                            onChange={(value) => setIsInacbgFinal(value)} // Mengubah nilai filter saat dipilih
                            allowClear
                            placeholder="Filter Final INACBG"
                        >
                            <Select.Option value="final">
                                Sudah Di-Final
                            </Select.Option>
                            <Select.Option value="not_final">
                                Belum Di-Final
                            </Select.Option>
                        </Select>
                    </Col>

                    <Col span={2}>
                        <div>
                            <Typography.Text>&nbsp;</Typography.Text>
                        </div>
                        <Button
                            block
                            type="primary"
                            onClick={() => {
                                const params = new URLSearchParams(
                                    window.location.search
                                );
                                params.set("page", 1);
                                params.set("per_page", perPage);
                                params.set("tanggal_keluar", tanggalKeluar);
                                params.set("kode_dokter", kodeDokter);
                                params.set("no_rm", noRM);
                                params.set("kode_bangsal", kodeBangsal);
                                params.set("is_inacbg_final", isInacbgFinal); // Mengirimkan filter is_inacbg_final
                                window.history.replaceState(
                                    null,
                                    "",
                                    `?${params.toString()}`
                                );

                                setPage(1);
                                fetchDataPasienInap(1, perPage);
                            }}
                        >
                            Cari
                        </Button>
                    </Col>
                    <Col span={2}>
                        <div>
                            <Typography.Text>&nbsp;</Typography.Text>
                        </div>
                        <Button
                            block
                            onClick={() => {
                                window.location.replace(
                                    `${route("rm.pasien-inap.list_inap")}`
                                );
                            }}
                        >
                            Reset
                        </Button>
                    </Col>
                </Row>

                <small>
                    total data: {totalData}. Page: {page}. Perpage: {perPage}
                </small>

                <Table
                    dataSource={dataPasienInaps}
                    columns={columnsInap}
                    size="small"
                    loading={loading}
                    rowKey="FTNO_TRANSAKSI"
                    scroll={{ x: "max-content" }}
                    pagination={{
                        simple: true,
                        current: page,
                        total: totalData,
                        pageSize: perPage,
                    }}
                    onChange={handleTableChange}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
