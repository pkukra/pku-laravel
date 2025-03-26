import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import {
    Table,
    Card,
    Modal,
    Input,
    Button,
    DatePicker,
    Row,
    Col,
    Select,
} from "antd";
import { EditOutlined } from "@ant-design/icons";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

import RanapMonitListModalDiagnosa from "./RanapMonitListModalDiagnosa";
import RanapMonitListModalProcedure from "./RanapMonitListModalProcedure";
import RanapMonitListModalBilling from "./RanapMonitListModalBilling";
import ModalCPPT from "./ModalCPPT";

export default function Index({ auth, bangsal }) {
    const columns = [
        {
            title: "No Transakasi",
            dataIndex: "FTNO_TRANSAKSI",
            key: "FTNO_TRANSAKSI",
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
            dataIndex: "FTKD_PASIEN",
            key: "FTKD_PASIEN",
            fixed: "left",
        },
        {
            title: "DPJP",
            dataIndex: "DPJP",
            key: "DPJP",
            fixed: "left",
        },
        {
            title: "Tanggal Masuk",
            dataIndex: "FTTGL_TRANSAKSI",
            key: "FTTGL_TRANSAKSI",
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
            key: "TOTAL_HARI",
            width: 50,
            align: "center",
            render: (_, record) => {
                const masuk = moment(record.FTTGL_TRANSAKSI);
                const keluar = record.PRWITGL_KELUAR
                    ? moment(record.PRWITGL_KELUAR)
                    : moment();
                return keluar.diff(masuk, "days");
            },
        },
        {
            title: "CPPT",
            dataIndex: "CPPT",
            key: "CPPT",
            render: (text, record) => (
                <>
                    <ModalCPPT
                        kode_reg={record?.FTNO_TRANSAKSI}
                        pasien={record}
                    />
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
                                key: "PEMERIKSAAN_PENUNJANG",
                                data_record: record,
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
                                key: "HASIL_PENUNJANG_ABNORMAL",
                                data_record: record,
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
            dataIndex: "KELAS_RAWAT",
            key: "KELAS_RAWAT",
        },
        {
            title: "Naik Kelas",
            dataIndex: "RAWAT_NAIK",
            key: "RAWAT_NAIK",
            render: (text, record) => (
                <>{naikKelasSanitize(record?.RAWAT_NAIK)}</>
            ),
        },
        {
            title: "Kemungkinan Kode Diagnosa",
            dataIndex: "FTNO_TRANSAKSI",
            key: "KODE_DIAGNOSA",
            render: (kodeReg) => (
                <>
                    {diagnosaData[kodeReg]?.map((diagnosa) => (
                        <React.Fragment>
                            {diagnosa?.MRPKD_PENYAKIT} <br />
                        </React.Fragment>
                    ))}
                    <RanapMonitListModalDiagnosa
                        pasien={{ FTNO_TRANSAKSI: kodeReg }}
                    />
                </>
            ),
        },
        {
            title: "Kemungkinan Kode Prosedur",
            dataIndex: "FTNO_TRANSAKSI",
            key: "KODE_PROCEDURE",
            render: (kodeReg) => (
                <>
                    {prosedurData[kodeReg]?.map((procedure) => (
                        <React.Fragment>
                            {procedure?.MRTKD_TINDAKAN} <br />
                        </React.Fragment>
                    ))}
                    <RanapMonitListModalProcedure
                        pasien={{ FTNO_TRANSAKSI: kodeReg }}
                    />
                </>
            ),
        },
        {
            title: "Billing Sementara",
            dataIndex: "BILLING_TEMP",
            key: "BILLING_TEMP",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <RanapMonitListModalBilling pasien={record} />
                </>
            ),
        },
        {
            title: "Perkiraan Klaim",
            dataIndex: "klaim",
            key: "klaim",
            render: (text, record) => (
                <>
                    {!naikKelasSanitize(record?.RAWAT_NAIK) &&
                        record?.FTTARIPINACBG}

                    {naikKelasSanitize(record?.RAWAT_NAIK) == 1 &&
                        record?.FTTARIPINACBG1}

                    {naikKelasSanitize(record?.RAWAT_NAIK) == 2 &&
                        record?.FTTARIPINACBG2}

                    {naikKelasSanitize(record?.RAWAT_NAIK) == "vip" &&
                        record?.FTTARIPINACBG1}
                </>
            ),
        },
        {
            title: "Konfirmasi Koder",
            dataIndex: "KONFIRMASI_KODER",
            key: "KONFIRMASI_KODER",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "KONFIRMASI_KODER",
                                data_record: record,
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
            title: "Konfirmasi Dokter Bangsal",
            dataIndex: "KONFIRMASI_DR_BANGSAL",
            key: "KONFIRMASI_DR_BANGSAL",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "KONFIRMASI_DR_BANGSAL",
                                data_record: record,
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
            title: "Follow Up SPV Bangsal",
            dataIndex: "FOLLOW_UP_SPV_BANGSAL",
            key: "FOLLOW_UP_SPV_BANGSAL",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "FOLLOW_UP_SPV_BANGSAL",
                                data_record: record,
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
            title: "Follow Up MPP",
            dataIndex: "FOLLOW_UP_MPP",
            key: "FOLLOW_UP_MPP",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    <a
                        onClick={() => {
                            handleOpenModal({
                                key: "FOLLOW_UP_MPP",
                                data_record: record,
                                value: text,
                            });
                        }}
                    >
                        <EditOutlined />
                    </a>
                </>
            ),
        },
    ];

    const [shouldFetch, setShouldFetch] = useState(false);
    const [selectedStatusRawat, setSelectedStatusRawat] = useState("dirawat");
    const [selectedNoRM, setSelectedNoRM] = useState(null);
    const [selectedYearMonth, setSelectedYearMonth] = useState(
        dayjs().format("YYYY-MM")
    );
    const [selectedBangsal, setSelectedBangsal] = useState("IK042");

    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(100);
    const [totalData, setTotalData] = useState(0);

    const [dataSource, setDataSource] = useState([]);
    const [loadingFetchData, setLoadingFetchData] = useState(false);
    const [openModalUpdate, setOpenModalUpdate] = useState(false);
    const [loadingSave, setLoadingSave] = useState(false);

    const [modalUpdateRecord, setModalUpdateRecord] = useState(null);
    const [modalUpdateKey, setModalUpdateKey] = useState(null);
    const [modalUpdateKodeReg, setModalUpdateKodeReg] = useState(null);
    const [modalUpdateValue, setModalUpdateValue] = useState(null);

    const [diagnosaData, setDiagnosaData] = useState({});
    const [prosedurData, setProsedurData] = useState({});

    const handleOpenModal = (param) => {
        setModalUpdateRecord(param?.data_record);
        setModalUpdateKey(param?.key);
        setModalUpdateKodeReg(param?.data_record?.FTNO_TRANSAKSI);
        setModalUpdateValue(param?.value);
        setOpenModalUpdate(true);
    };

    const fetchDiagnosaProsedur = async (pasienList) => {
        await Promise.allSettled(
            pasienList.map(async (pasien) => {
                try {
                    const [diagnosaRes, prosedurRes] = await Promise.all([
                        axios.get(
                            route("casemix.ranap-monit.list_diagnosa", {
                                kode_reg: pasien.FTNO_TRANSAKSI,
                            })
                        ),
                        axios.get(
                            route("casemix.ranap-monit.list_procedure", {
                                kode_reg: pasien.FTNO_TRANSAKSI,
                            })
                        ),
                    ]);

                    // Update state setelah tiap pasien berhasil di-fetch
                    setDiagnosaData((prev) => ({
                        ...prev,
                        [pasien.FTNO_TRANSAKSI]: diagnosaRes.data?.data || [],
                    }));

                    console.log(prosedurRes.data?.data);

                    setProsedurData((prev) => ({
                        ...prev,
                        [pasien.FTNO_TRANSAKSI]: prosedurRes.data?.data || [],
                    }));
                } catch (error) {
                    console.error(
                        `Error fetching data for ${pasien.FTNO_TRANSAKSI}`,
                        error
                    );
                }
            })
        );
    };

    const naikKelasSanitize = (naik_kelas) => {
        if (!naik_kelas) {
            return null;
        }
        if (naik_kelas === "-- Pilih --") {
            return null;
        }

        // Pola regex untuk menangkap angka di awal dan teks setelahnya
        const match = naik_kelas.match(/^(\d+)\.\s*(.+)$/);
        if (!match) {
            return null;
        }

        const [, angka, nama] = match; // Ambil angka dan nama kelas
        const mapping = {
            1: "1",
            2: "vip",
            3: "1",
            4: "2",
        };

        return mapping[angka] || null;
    };

    const handleUpdate = () => {
        if (modalUpdateValue?.length > 160) {
            return alert("Maksimal karakter 160");
        }

        setLoadingSave(true);

        axios
            .post(
                route("casemix.ranap-monit.update_monit_row", {
                    kode_reg: modalUpdateKodeReg,
                }),
                {
                    key: modalUpdateKey,
                    data: modalUpdateValue,
                }
            )
            .then((response) => {
                console.log(response?.data);
            })
            .catch((error) => {})
            .finally(() => {
                setLoadingSave(false);
                setOpenModalUpdate(false);
                fetchData();
            });
    };

    const fetchData = async () => {
        setLoadingFetchData(true);
        try {
            const [year, month] = selectedYearMonth.split("-");
            const { data } = await axios.get(
                route("casemix.ranap-monit.list_pasien_data"),
                {
                    params: {
                        page: page,
                        per_page: perPage,
                        year,
                        month,
                        status: selectedStatusRawat,
                        nomer_rm: selectedNoRM,
                        bangsal_induk: selectedBangsal,
                    },
                }
            );

            setDataSource(data.pasiens);
            setTotalData(data.total);

            fetchDiagnosaProsedur(data.pasiens);
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoadingFetchData(false);
        }
    };

    const handleCari = () => {
        setPage(1); // Set nilai page
        setShouldFetch(true); // Aktifkan trigger untuk fetchData()
    };

    useEffect(() => {
        if (shouldFetch) {
            fetchData();
            setShouldFetch(false); // Matikan trigger setelah fetch
        }
    }, [shouldFetch]);

    const optionsBangsal = bangsal.map((item) => ({
        value: item.FMKAMAR_ID,
        label: item.FMKAMARN,
    }));

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
                            allowClear={false}
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
                    <Col span={3}>
                        <Select
                            defaultValue={selectedStatusRawat}
                            style={{ width: 150 }}
                            onChange={(value) => setSelectedStatusRawat(value)}
                            options={[
                                { value: "dirawat", label: "Dirawat" },
                                {
                                    value: "sudah_pulang",
                                    label: "Sudah Pulang",
                                },
                                { value: "semua", label: "Semua" },
                            ]}
                        />
                    </Col>
                    <Col span={3}>
                        <Select
                            value={selectedBangsal}
                            style={{ width: 150 }}
                            options={optionsBangsal}
                            onChange={(value) => setSelectedBangsal(value)}
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
                    rowKey="FTNO_TRANSAKSI"
                    scroll={{
                        x: 2000,
                        y: 600,
                    }}
                    pagination={{
                        // simple: true,
                        current: page,
                        total: totalData,
                        pageSize: perPage,
                        onChange: (currentPage, currentPageSize) => {
                            setPage(currentPage);
                            setPerPage(currentPageSize);
                            fetchData();
                        },
                    }}
                />
            </Card>
            <Modal
                destroyOnClose
                title={modalUpdateKey
                    ?.replace(/_/g, " ")
                    .replace(/\b\w/g, (char) => char.toUpperCase())}
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
                Detail Pasien:
                <p>
                    No RM: <strong>{modalUpdateRecord?.FTKD_PASIEN} </strong>{" "}
                    Nama Pasien:{" "}
                    <strong>{modalUpdateRecord?.NAMAPASIEN}</strong> No
                    Transakasi:{" "}
                    <strong>{modalUpdateRecord?.FTNO_TRANSAKSI} </strong>
                </p>
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
