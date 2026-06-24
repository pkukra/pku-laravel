import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import {
    Card,
    Button,
    Table,
    Row,
    Col,
    DatePicker,
    Input,
    Select,
    Typography, Popover,
} from "antd";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";

import KumpulanButtonKlaimInapOnList from "../../Klaim/Inap/KumpulanButtonKlaimInapOnList";

export default function Index({ auth, bangsal }) {
    const queryParams = new URLSearchParams(window.location.search);

    const initialPage = parseInt(queryParams.get("page")) || 1;
    const initialPerPage = parseInt(queryParams.get("per_page")) || 100;
    const initialTanggalKeluar =
        queryParams.get("tanggal_keluar") || moment().format("YYYY-MM-DD");
    const initialKodeDokter = queryParams.get("kode_dokter") || "";
    const initialNoRM = queryParams.get("no_rm") || "";
    const initialKodeBangsal = queryParams.get("kode_bangsal") || null;
    const initialIsInacbgFinal =
        queryParams.get("is_inacbg_final") || "not_final";

    const [loading, setLoading] = useState(false);
    const [dataPasienInaps, setDataPasienInaps] = useState([]);
    const [totalData, setTotalData] = useState(0);
    const [page, setPage] = useState(initialPage);
    const [perPage, setPerPage] = useState(initialPerPage);
    const [tanggalKeluar, setTanggalKeluar] = useState(initialTanggalKeluar);
    const [kodeDokter, setKodeDokter] = useState(initialKodeDokter);
    const [noRM, setNoRM] = useState(initialNoRM);
    const [kodeBangsal, setKodeBangsal] = useState(initialKodeBangsal);
    const [isInacbgFinal, setIsInacbgFinal] = useState(initialIsInacbgFinal);
    const [customerData, setCustomerData] = useState([]);

    const buildQueryParams = (params) => {
        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== "" && value !== undefined) {
                query.set(key, value);
            }
        });
        return query.toString();
    };

    const fetchDataPasienInap = async (
        pageVal = page,
        perPageVal = perPage,
    ) => {
        setLoading(true);
        try {
            const paramObj = {
                tanggal_keluar: tanggalKeluar,
                page: pageVal,
                per_page: perPageVal,
                kode_dokter: kodeDokter,
                no_rm: noRM,
                kode_bangsal: kodeBangsal,
                is_inacbg_final: isInacbgFinal,
            };

            const queryStr = buildQueryParams(paramObj);
            window.history.replaceState(null, "", `?${queryStr}`);

            const response = await axios.get(
                route("rm.pasien-inap.list_inap_data"),
                { params: paramObj },
            );

            setDataPasienInaps(response?.data?.data?.data || []);
            setTotalData(response?.data?.data?.total || 0);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            fetchCustomers();
            setLoading(false);
        }
    };

    const handleTableChange = (pagination) => {
        const newPage = pagination.current;
        const newPerPage = pagination.pageSize;

        setPage(newPage);
        setPerPage(newPerPage);
        fetchDataPasienInap(newPage, newPerPage);
    };

    const fetchCustomers = async () => {
        try {
            const response = await axios.get(route("rm.get_cusromers"));
            setCustomerData(response?.data || []);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
        }
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
                        <Typography.Text strong>Tanggal Keluar</Typography.Text>
                        <DatePicker
                            allowClear={false}
                            value={dayjs(tanggalKeluar)}
                            onChange={(dateMoment, dateString) =>
                                setTanggalKeluar(dateString)
                            }
                            disabledDate={(current) =>
                                current && current > moment().endOf("day")
                            }
                        />
                    </Col>
                    <Col span={3}>
                        <Typography.Text strong>No RM</Typography.Text>
                        <Input
                            allowClear
                            value={noRM}
                            onChange={(e) => setNoRM(e.target.value)}
                            placeholder="No RM"
                        />
                    </Col>
                    <Col span={4}>
                        <Typography.Text strong>Bangsal</Typography.Text>
                        <Select
                            style={{ width: "100%" }}
                            options={optionsBangsal}
                            value={kodeBangsal}
                            onChange={(value) => setKodeBangsal(value)}
                            allowClear
                            placeholder="Pilih Bangsal"
                        />
                    </Col>
                    <Col span={3}>
                        <Typography.Text strong>Kode Dokter</Typography.Text>
                        <Input
                            allowClear
                            value={kodeDokter}
                            onChange={(e) => setKodeDokter(e.target.value)}
                            placeholder="Kode Dokter"
                        />
                    </Col>
                    <Col span={4}>
                        <Typography.Text strong>
                            Filter Final INACBG
                        </Typography.Text>
                        <Select
                            style={{ width: "100%" }}
                            value={isInacbgFinal}
                            onChange={(value) => setIsInacbgFinal(value)}
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
                        <Typography.Text>&nbsp;</Typography.Text>
                        <Button
                            block
                            type="primary"
                            onClick={() => {
                                const paramObj = {
                                    page: 1,
                                    per_page: perPage,
                                    tanggal_keluar: tanggalKeluar,
                                    kode_dokter: kodeDokter,
                                    no_rm: noRM,
                                    kode_bangsal: kodeBangsal,
                                    is_inacbg_final: isInacbgFinal,
                                };
                                const queryStr = buildQueryParams(paramObj);
                                window.history.replaceState(
                                    null,
                                    "",
                                    `?${queryStr}`,
                                );

                                setPage(1);
                                fetchDataPasienInap(1, perPage);
                            }}
                        >
                            Cari
                        </Button>
                    </Col>
                    <Col span={2}>
                        <Typography.Text>&nbsp;</Typography.Text>
                        <Button
                            block
                            onClick={() => {
                                window.location.replace(
                                    `${route("rm.pasien-inap.list_inap")}`,
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
                    columns={[
                        {
                            title: "Tanggal Masuk",
                            dataIndex: "FTTGL_TRANSAKSI",
                            render: (_, record) => (
                                <>
                                    {moment(record?.FTTGL_TRANSAKSI).format(
                                        "DD/MM/YYYY",
                                    )}
                                </>
                            ),
                        },
                        {
                            title: "Tanggal Keluar",
                            dataIndex: "TGL_KELUAR",
                            render: (_, record) => (
                                <>
                                    {record?.TGL_KELUAR &&
                                        moment(record?.TGL_KELUAR).format(
                                            "DD/MM/YYYY",
                                        )}
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
                        },
                        {
                            title: "Dokter",
                            dataIndex: "FMDDOKTERN",
                            render: (_, record) => (
                                <>
                                    {record?.PRWIKD_DOKTER} -{" "}
                                    {record?.FMDDOKTERN}
                                </>
                            ),
                        },
                        {
                            title: "Kelompok",
                            dataIndex: "PRWIKD_CUSTOMER",
                            render: (value) => {
                                const cust = customerData?.find(
                                    (c) => c?.CUSID === value,
                                );
                                const name = cust ? cust?.NAME : value;
                                const isBPJS =
                                    value === "X002" || value === "X003";
                                const displayName = isBPJS
                                    ? `BPJS ${name}`
                                    : name;

                                return (
                                    <span
                                        style={{
                                            color: isBPJS ? "green" : "inherit",
                                        }}
                                    >
                                        {displayName}
                                    </span>
                                );
                            },
                        },
                        {
                            title: "Final INACBG",
                            dataIndex: "FKUNCI_VALIDASI",
                            align: "center",
                            render: (_, record) => (
                                <>
                                    {record?.FKUNCI_VALIDASI == 1 ? "✅" : "❌"}
                                </>
                            ),
                        },
                        {
                            title: "Action",
                            align: "center",
                            dataIndex: "action",
                            render: (_, record) => (
                                <>
                                    <a
                                        href={route("rm.pasien-inap.detail", {
                                            kode_reg: record?.FTNO_TRANSAKSI,
                                        })}
                                    >
                                        <Button type="primary" size="small">
                                            Tampilkan
                                        </Button>
                                    </a>{" "}
                                    <Popover
                                        title={`${record?.NAMAPASIEN} - ${record?.FTKD_PASIEN}`}
                                        trigger="click"
                                        content={
                                            <KumpulanButtonKlaimInapOnList
                                                nama_pasien={record?.NAMAPASIEN}
                                                nomer_rm={record?.FTKD_PASIEN}
                                                kode_reg={
                                                    record?.FTNO_TRANSAKSI
                                                }
                                            />
                                        }
                                    >
                                        <Button type="primary" size="small">
                                            Klaim
                                        </Button>
                                    </Popover>
                                </>
                            ),
                        },
                    ]}
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
