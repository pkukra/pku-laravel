import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Table, Card, Modal, Input, Button, DatePicker, Row, Col } from "antd";
import { EditOutlined } from "@ant-design/icons";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

import RanapMonitListModalDiagnosa from "./RanapMonitListModalDiagnosa";
import RanapMonitListModalProcedure from "./RanapMonitListModalProcedure";

export default function Index({ auth }) {
    const columns = [
        {
            title: "No Transakasi",
            dataIndex: "PRWINO_TRANSAKSI",
            key: "PRWINO_TRANSAKSI",
            fixed: "left",
        },
        {
            title: "Nama Pasien",
            dataIndex: "NAMAPASIEN",
            key: "NAMAPASIEN",
            fixed: "left",
        },
        {
            title: "Nomer RM",
            dataIndex: "PRWIKD_PASIEN",
            key: "PRWIKD_PASIEN",
            fixed: "left",
        },
        {
            title: "DPJP",
            dataIndex: "FMDDOKTERN",
            key: "FMDDOKTERN",
            fixed: "left",
        },
        {
            title: "Tanggal Masuk",
            dataIndex: "PRWITGL_MASUK",
            key: "PRWITGL_MASUK",
            render: (text) => moment(text).format("D-M-YYYY"),
        },
        {
            title: "Tanggal Keluar",
            dataIndex: "PRWITGL_KELUAR",
            key: "PRWITGL_KELUAR",
            render: (text) => (text ? moment(text).format("D-M-YYYY") : ""),
        },
        {
            title: "Total Hari",
            dataIndex: "TOTAL_HARI",
            key: "TOTAL_HARI",
            width: 50,
            align: "center",
        },
        {
            title: "Diagnosa Utama",
            dataIndex: "FS_DIAGNOSA",
            key: "FS_DIAGNOSA",
            render: (text) => (
                <div dangerouslySetInnerHTML={{ __html: text }} />
            ),
        },
        {
            title: "Diagnosa Sekunder",
            dataIndex: "DIAGNOSA_SEKUNDER",
            key: "DIAGNOSA_SEKUNDER",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "diagnosa_sekunder",
                                kode_reg: record?.PRWINO_TRANSAKSI,
                                value: text,
                            });
                        }}
                    >
                        <EditOutlined />
                    </a>
                </>
            ),
        },
        {
            title: "Tindakan",
            dataIndex: "TINDAKAN",
            key: "TINDAKAN",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "tindakan",
                                kode_reg: record?.PRWINO_TRANSAKSI,
                                value: text,
                            });
                        }}
                    >
                        <EditOutlined />
                    </a>
                </>
            ),
        },
        {
            title: "Pemeriksaan Penunjang",
            dataIndex: "PEMERIKSAAN_PENUNJANG",
            key: "PEMERIKSAAN_PENUNJANG",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "pemeriksaan_penunjang",
                                kode_reg: record?.PRWINO_TRANSAKSI,
                                value: text,
                            });
                        }}
                    >
                        <EditOutlined />
                    </a>
                </>
            ),
        },
        {
            title: "Hasil Penunjang Abnormal",
            dataIndex: "HASIL_PENUNJANG_ABNORMAL",
            key: "HASIL_PENUNJANG_ABNORMAL",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "hasil_penunjang_abnormal",
                                kode_reg: record?.PRWINO_TRANSAKSI,
                                value: text,
                            });
                        }}
                    >
                        <EditOutlined />
                    </a>
                </>
            ),
        },
        {
            title: "Hak Kelas",
            dataIndex: "LOS",
            key: "DPJP",
        },
        {
            title: "Naik Kelas",
            dataIndex: "NAIK_KELAS",
            key: "NAIK_KELAS",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "naik_kelas",
                                kode_reg: record?.PRWINO_TRANSAKSI,
                                value: text,
                            });
                        }}
                    >
                        <EditOutlined />
                    </a>
                </>
            ),
        },
        {
            title: "Kemungkinan Kode Dignosis",
            dataIndex: "KODE_DIAGNOSA",
            key: "KODE_DIAGNOSA",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <RanapMonitListModalDiagnosa pasien={record} />
                </>
            ),
        },
        {
            title: "Kemungkinan Kode Prosedur",
            dataIndex: "KODE_PROCEDURE",
            key: "KODE_PROCEDURE",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <RanapMonitListModalProcedure pasien={record} />
                </>
            ),
        },
        {
            title: "Total Billing",
            dataIndex: "LOS",
            key: "DPJP",
        },
        {
            title: "Perkiraan Klaim",
            dataIndex: "LOS",
            key: "DPJP",
        },
    ];

    const [selectedNoRM, setSelectedNoRM] = useState(null);
    const [selectedYearMonth, setSelectedYearMonth] = useState(null);

    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(10);
    const [totalData, setTotalData] = useState(10);

    const [dataSource, setDataSource] = useState([]);
    const [loadingFetchData, setLoadingFetchData] = useState(false);
    const [openModalUpdate, setOpenModalUpdate] = useState(false);
    const [loadingSave, setLoadingSave] = useState(false);

    const [modalUpdateKey, setModalUpdateKey] = useState(null);
    const [modalUpdateKodeReg, setModalUpdateKodeReg] = useState(null);
    const [modalUpdateValue, setModalUpdateValue] = useState(null);

    const handleOpenModal = (param) => {
        setModalUpdateKey(param?.key);
        setModalUpdateKodeReg(param?.kode_reg);
        setModalUpdateValue(param?.value);
        setOpenModalUpdate(true);
    };

    const handleUpdate = () => {
        setLoadingSave(true);
        let payload = {};
        if (modalUpdateKey === "diagnosa_sekunder") {
            payload = {
                diagnosa_sekunder: modalUpdateValue,
            };
        }

        if (modalUpdateKey === "tindakan") {
            payload = {
                tindakan: modalUpdateValue,
            };
        }

        if (modalUpdateKey === "pemeriksaan_penunjang") {
            payload = {
                pemeriksaan_penunjang: modalUpdateValue,
            };
        }

        if (modalUpdateKey === "hasil_penunjang_abnormal") {
            payload = {
                hasil_penunjang_abnormal: modalUpdateValue,
            };
        }

        if (modalUpdateKey === "naik_kelas") {
            payload = {
                naik_kelas: modalUpdateValue,
            };
        }

        axios
            .post(
                route("casemix.ranap-monit.update_monit_row", {
                    kode_reg: modalUpdateKodeReg,
                }),
                payload
            )
            .then((response) => {
                console.log(response?.data);
            })
            .catch((error) => {})
            .finally(() => {
                setLoadingSave(false);
                setOpenModalUpdate(false);
                fetchData(selectedYearMonth, selectedNoRM, page, perPage);
            });
    };

    const fetchData = async (yearMonth, nomerRM, page, perPage) => {
        setLoadingFetchData(true);
        try {
            const [year, month] = yearMonth.split("-");
            const { data } = await axios.get(
                route("casemix.ranap-monit.list_pasien_data"),
                {
                    params: {
                        page: page,
                        per_page: perPage,
                        year,
                        month,
                        nomer_rm: nomerRM,
                    },
                }
            );

            setDataSource(data.pasiens);
            setTotalData(data.total);
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoadingFetchData(false);
        }
    };

    const handleCari = () => {
        setPage(1);
        fetchData(selectedYearMonth, selectedNoRM, 1, perPage);
    };

    useEffect(() => {
        setSelectedYearMonth(dayjs().format("YYYY-MM"));
        fetchData(selectedYearMonth, selectedNoRM, page, perPage);
    }, []);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    Pasien Ranap
                </p>
            }
        >
            <Head title="Pasien Ranap" />
            <Card title="Pasien Ranap">
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={2}>
                        <DatePicker
                            value={dayjs(selectedYearMonth, "YYYY-MM")}
                            onChange={(date, dateString) => {
                                setSelectedYearMonth(dateString);
                            }}
                            picker="month"
                            placeholder="Pilih Bulan/Tahun"
                        />
                    </Col>
                    <Col span={2}>
                        <Input
                            allowClear
                            placeholder="Cari Nomor RM"
                            value={selectedNoRM}
                            onChange={(e) => {
                                const value = e.target.value;
                                setSelectedNoRM(value);
                            }}
                        />
                    </Col>
                    <Col span={2}>
                        <Button type="primary" onClick={handleCari}>
                            Cari
                        </Button>
                    </Col>
                </Row>
                <small>total data: {totalData}</small>
                <Table
                    bordered
                    loading={loadingFetchData}
                    dataSource={dataSource}
                    columns={columns}
                    size="small"
                    rowKey="PRWINO_TRANSAKSI"
                    scroll={{
                        x: 2000,
                        y: 600,
                    }}
                    pagination={{
                        simple: true,
                        current: page,
                        total: totalData,
                        pageSize: perPage,
                        onChange: (currentPage, currentPageSize) => {
                            console.log(currentPage, currentPageSize);
                            setPage(currentPage);
                            setPerPage(currentPageSize);
                            fetchData(
                                selectedYearMonth,
                                selectedNoRM,
                                currentPage,
                                currentPageSize
                            );
                        },
                    }}
                />
            </Card>
            <Modal
                destroyOnClose
                title={modalUpdateKey}
                open={openModalUpdate}
                closable={false}
                width={700}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setOpenModalUpdate(false)}
                        loading={loadingSave}
                    >
                        Cancel
                    </Button>,
                    <Button
                        key="submit"
                        type="primary"
                        onClick={handleUpdate}
                        loading={loadingSave}
                    >
                        Simpan
                    </Button>,
                ]}
            >
                <p>{modalUpdateKodeReg}</p>
                {modalUpdateKey === "naik_kelas" ? (
                    <Input
                        value={modalUpdateValue}
                        onChange={(e) => setModalUpdateValue(e.target.value)}
                    />
                ) : (
                    <ReactQuill
                        theme="snow"
                        value={modalUpdateValue}
                        onChange={setModalUpdateValue}
                    />
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
