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
    Typography,
    notification,
} from "antd";
import { EditOutlined } from "@ant-design/icons";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

import RanapMonitListModalDiagnosa from "./RanapMonitListModalDiagnosa";
import RanapMonitListModalProcedure from "./RanapMonitListModalProcedure";
import PasienMonitModalObat from "./PasienMonitModalObat";
import ModalCPPT from "./ModalCPPT";
import BridgingData from "./BridgingData";

const Textarea = Input.TextArea;

export default function Index({ auth, role, bangsal }) {
    const rolename = role?.name ?? null;

    const columns = [
        {
            title: "No RM & Kode Reg",
            dataIndex: "FTNO_TRANSAKSI",
            key: "FTNO_TRANSAKSI",
            fixed: "left",
            // width: 115,
            render: (text, record) => (
                // Menambahkan link menggunakan route helper untuk membangun URL dinamis
                <>
                    {record?.FTKD_PASIEN} <br />
                    <a
                        href={route("rm.pasien-inap.detail", {
                            kode_reg: record?.FTNO_TRANSAKSI,
                        })}
                    >
                        {text}
                    </a>
                </>
            ),
        },
        {
            title: "Nama Pasien",
            dataIndex: "NAMAPASIEN",
            key: "NAMAPASIEN",
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

                // Calculate the difference in days and ensure at least 1 day is counted
                const totalDays = keluar.diff(masuk, "days");

                // If masuk is today, we need to add 1 to make sure it's counted as 1 day
                return totalDays > 0 ? totalDays + 1 : 1; // Ensure it's at least 1 day
            },
        },
        {
            title: "Rujukan",
            dataIndex: "Rujukan",
            key: "Rujukan",
            width: 110,
            render: (text, record) => (
                <>
                    <ModalCPPT
                        kode_reg={record?.FTNO_TRANSAKSI}
                        pasien={record}
                    />

                    <PasienMonitModalObat pasien={record} />
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
                    {(rolename == "perawat" || rolename == "spv_bangsal") && (
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
                    )}
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
                    {(rolename == "perawat" || rolename == "spv_bangsal") && (
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
                    )}
                </>
            ),
        },
        {
            title: "Penjamin & Hak Kelas",
            dataIndex: "PRWIKD_CUSTOMER",
            key: "PRWIKD_CUSTOMER",
            render: (value, record) => {
                const cust = customerData?.find((c) => c?.CUSID === value);
                const name = cust ? cust?.NAME : value;
                const isBPJS = value === "X002" || value === "X003";
                const displayName = isBPJS ? `BPJS ${name}` : name;

                return (
                    <>
                        <span
                            style={{
                                color: isBPJS ? "green" : "inherit",
                            }}
                        >
                            {displayName}
                        </span>{" "}
                        <br />
                        {isBPJS && <span>kelas: {record?.KELAS_RAWAT}</span>}
                    </>
                );
            },
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
            title: "Kemungkinan Kode Diagnosa & Prosedur",
            dataIndex: "FTNO_TRANSAKSI",
            key: "DIAGNOSA_LENGKAP",
            render: (text, record) => {
                return (
                    <>
                        {/* Diagnosa */}
                        {record?.DIAGNOSA_LENGKAP?.map((item, idx) => {
                            const content = `${item.code}${
                                item.is_code_warning ? " (rawan pending)" : ""
                            }`;
                            return (
                                <span
                                    key={`diag-${idx}`}
                                    style={{
                                        color: item.is_code_warning
                                            ? "red"
                                            : "inherit",
                                    }}
                                >
                                    {content}
                                    {idx < record.DIAGNOSA_LENGKAP.length - 1
                                        ? ", "
                                        : ""}
                                </span>
                            );
                        })}

                        {/* Tindakan */}
                        {record?.TINDAKAN_LENGKAP?.length > 0 && (
                            <>
                                <hr />
                                {record.TINDAKAN_LENGKAP.map((item, idx) => {
                                    const content = `${item.code}${
                                        item.is_code_warning
                                            ? " (rawan pending)"
                                            : ""
                                    }`;
                                    return (
                                        <span
                                            key={`proc-${idx}`}
                                            style={{
                                                color: item.is_code_warning
                                                    ? "red"
                                                    : "inherit",
                                            }}
                                        >
                                            {content}
                                            {idx <
                                            record.TINDAKAN_LENGKAP.length - 1
                                                ? ", "
                                                : ""}
                                        </span>
                                    );
                                })}
                            </>
                        )}
                    </>
                );
            },
        },
        {
            title: "Alert Kode Diagnosa & Prosedur",
            dataIndex: "ALERTS",
            key: "KODE_DIAGNOSA",
            width: 170,
            render: (alerts) => {
                if (!alerts || alerts?.length === 0) {
                    return <span style={{ color: "gray" }}>-</span>;
                }
                return (
                    <ul style={{ paddingLeft: 16, margin: 0 }}>
                        {alerts.map((a, idx) => (
                            <li key={idx}>
                                <strong>{a.icd_code}</strong>{" "}
                                <span
                                    dangerouslySetInnerHTML={{
                                        __html: a.description,
                                    }}
                                />
                            </li>
                        ))}
                    </ul>
                );
            },
        },
        {
            title: "Perkiraan Klaim (Rp)",
            dataIndex: "klaim",
            key: "klaim",
            align: "right",
            render: (text, record) => {
                let perkiraanKlaim = !naikKelasSanitize(record?.RAWAT_NAIK)
                    ? record?.FTTARIPINACBG
                    : naikKelasSanitize(record?.RAWAT_NAIK) == 1
                    ? record?.FTTARIPINACBG1
                    : naikKelasSanitize(record?.RAWAT_NAIK) == 2
                    ? record?.FTTARIPINACBG2
                    : naikKelasSanitize(record?.RAWAT_NAIK) == "vip"
                    ? record?.FTTARIPINACBG1
                    : null; // Jika tidak ada klaim, set null

                return (
                    <>
                        {perkiraanKlaim !== null
                            ? Math.abs(perkiraanKlaim).toLocaleString()
                            : "-"}
                        {/* {record?.NO_SEP && rolename == "koder" && (
                            <BridgingData
                                pasien={record}
                                refetchData={fetchData}
                            />
                        )} */}
                    </>
                );
            },
        },
        {
            title: "Billing Sementara (Rp)",
            dataIndex: "TOTAL_BILL",
            key: "TOTAL_BILL",
            align: "right",
            render: (text, record) => {
                let totalBill = parseFloat(text) || 0;
                let perkiraanKlaim = !naikKelasSanitize(record?.RAWAT_NAIK)
                    ? record?.FTTARIPINACBG
                    : naikKelasSanitize(record?.RAWAT_NAIK) == 1
                    ? record?.FTTARIPINACBG1
                    : naikKelasSanitize(record?.RAWAT_NAIK) == 2
                    ? record?.FTTARIPINACBG2
                    : naikKelasSanitize(record?.RAWAT_NAIK) == "vip"
                    ? record?.FTTARIPINACBG1
                    : null; // Jika bukan BPJS, perkiraan klaim null

                // Jika perkiraan klaim null, tidak perlu hitung selisih
                if (perkiraanKlaim === null) {
                    return (
                        <Typography.Text>
                            <div
                                dangerouslySetInnerHTML={{
                                    __html: Math.abs(text).toLocaleString(),
                                }}
                            />
                        </Typography.Text>
                    );
                }

                let selisih = totalBill - perkiraanKlaim;
                let color = selisih > 0 ? "red" : "green"; // Rugi (merah) jika totalBill lebih besar

                return (
                    <div>
                        <Typography.Text style={{ color }}>
                            {Math.abs(text).toLocaleString()}
                        </Typography.Text>
                        <br />
                        <Typography.Text style={{ color }}>
                            {selisih >= 0
                                ? `-${selisih.toLocaleString()}`
                                : `+${Math.abs(selisih).toLocaleString()}`}
                        </Typography.Text>
                    </div>
                );
            },
        },
        {
            title: "Konfirmasi Koder",
            dataIndex: "KONFIRMASI_KODER",
            key: "KONFIRMASI_KODER",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    {rolename == "koder" && (
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
                    )}
                </>
            ),
        },
        {
            title: "Rekomendasi Dokter Bangsal",
            dataIndex: "REKOMENDASI_DOKTER_BANGSAL",
            key: "REKOMENDASI_DOKTER_BANGSAL",
            render: (text, record) => (
                <>
                    <div dangerouslySetInnerHTML={{ __html: text }} />
                    {rolename == "dokter" && (
                        <a
                            onClick={() => {
                                handleOpenModal({
                                    key: "REKOMENDASI_DOKTER_BANGSAL",
                                    data_record: record,
                                    value: text,
                                });
                            }}
                        >
                            <EditOutlined />
                        </a>
                    )}
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
                    {rolename == "spv_bangsal" && (
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
                    )}
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
                    {rolename == "mpp" && (
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
                    )}
                </>
            ),
        },
    ];

    const [scrollY, setScrollY] = useState(400); // default scroll height
    const [shouldFetch, setShouldFetch] = useState(false);
    const [selectedStatusRawat, setSelectedStatusRawat] = useState("dirawat");
    const [selectedNoRM, setSelectedNoRM] = useState(null);
    const [selectedYearMonth, setSelectedYearMonth] = useState(null);
    // const [selectedYearMonth, setSelectedYearMonth] = useState(
    //     dayjs().format("YYYY-MM")
    // );
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

    const [diagnosaData, setDiagnosaData] = useState([]);
    const [prosedurData, setProsedurData] = useState([]);
    const [abortController, setAbortController] = useState(null);
    const [customerData, setCustomerData] = useState([]);

    const handleOpenModal = (param) => {
        setModalUpdateRecord(param?.data_record);
        setModalUpdateKey(param?.key);
        setModalUpdateKodeReg(param?.data_record?.FTNO_TRANSAKSI);
        setModalUpdateValue(param?.value);
        setOpenModalUpdate(true);
    };

    // const fetchDiagnosaProsedur = async (pasienList) => {
    //     // Batalkan request sebelumnya jika ada
    //     if (abortController) {
    //         abortController.abort();
    //     }

    //     // Buat AbortController baru
    //     const controller = new AbortController();
    //     setAbortController(controller);

    //     await Promise.allSettled(
    //         pasienList.map(async (pasien) => {
    //             try {
    //                 const [diagnosaRes, prosedurRes] = await Promise.all([
    //                     axios.get(
    //                         route("casemix.ranap-monit.list_diagnosa", {
    //                             kode_reg: pasien.FMNOSEP,
    //                         }),
    //                         { signal: controller.signal }
    //                     ),
    //                     axios.get(
    //                         route("casemix.ranap-monit.list_procedure", {
    //                             kode_reg: pasien.FMNOSEP,
    //                         }),
    //                         { signal: controller.signal }
    //                     ),
    //                 ]);

    //                 // Update state setelah tiap pasien berhasil di-fetch
    //                 setDiagnosaData((prev) => ({
    //                     ...prev,
    //                     [pasien.FTNO_TRANSAKSI]: diagnosaRes.data?.data || [],
    //                 }));

    //                 setProsedurData((prev) => ({
    //                     ...prev,
    //                     [pasien.FTNO_TRANSAKSI]: prosedurRes.data?.data || [],
    //                 }));
    //             } catch (error) {
    //                 if (axios.isCancel(error)) {
    //                     console.log("Fetch dibatalkan:", pasien.FTNO_TRANSAKSI);
    //                 } else {
    //                     console.error(
    //                         `Error fetching data for ${pasien.FTNO_TRANSAKSI}`,
    //                         error
    //                     );
    //                 }
    //             }
    //         })
    //     );
    // };

    const fetchCustomers = async () => {
        try {
            const response = await axios.get(route("rm.get_cusromers"));
            setCustomerData(response?.data || []);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
        }
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
        if (modalUpdateValue?.length > 1000) {
            return alert("Maksimal karakter 1000");
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
                setLoadingSave(false);
                setOpenModalUpdate(false);
                fetchData();
                return notification.success({
                    placement: "bottomRight",
                    message: "Sukses",
                    description: "Data berhasil disimpan",
                });
            })
            .catch((error) => {
                return notification.error({
                    placement: "bottomRight",
                    message: "Gagal Memperbarui Data",
                    description: error.response?.data?.message,
                });
            });
    };

    const fetchData = async () => {
        setLoadingFetchData(true);
        try {
            const [year, month] = selectedYearMonth
                ? selectedYearMonth.split("-")
                : [null, null];
            const { data } = await axios.get(
                route("casemix.ranap-monit.list_pasien_data"),
                {
                    params: {
                        page: page,
                        per_page: perPage,
                        year: year,
                        month: month,
                        status: selectedStatusRawat,
                        nomer_rm: selectedNoRM,
                        bangsal_induk: selectedBangsal,
                    },
                }
            );
            fetchCustomers();
            setDataSource(data.pasiens);
            setTotalData(data.total);

            // fetchDiagnosaProsedur(data.pasiens);
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoadingFetchData(false);
        }
    };

    const hadleCetakKlaim = () => {
        const [year, month] = selectedYearMonth?.split("-") || [];
        const baseUrl = route(
            "casemix.ranap-monit.download_pasien_data_xls"
        ).toString();

        const query = new URLSearchParams({
            ...(year && { year }),
            ...(month && { month }),
            ...(selectedBangsal && { bangsal_induk: selectedBangsal }),
        });

        window.open(`${baseUrl}?${query}`, "_blank");
    };

    const handleCari = () => {
        if (selectedBangsal == "all") {
            return alert("Pilih salah satu bangsal");
        }

        setPage(1); // Set nilai page
        setShouldFetch(true); // Aktifkan trigger untuk fetchData()
    };

    useEffect(() => {
        const screenWidth = window.innerWidth;
        if (screenWidth > 1280) {
            setScrollY(500); // Untuk layar besar
        } else {
            setScrollY(400); // Untuk layar kecil
        }

        if (shouldFetch) {
            fetchData();
            setShouldFetch(false); // Matikan trigger setelah fetch
        }
    }, [shouldFetch]);

    const optionsBangsal = [
        { value: "all", label: "Semua" }, // opsi default
        ...bangsal.map((item) => ({
            value: item.FMKAMAR_ID,
            label: item.FMKAMARN,
        })),
    ];

    if (!rolename) {
        return (
            <AuthenticatedLayout
                user={auth.user}
                header={
                    <p className="font-semibold text-lg text-gray-800 leading-tight">
                        Pasien Ranap
                    </p>
                }
            >
                Akses tidak dijikan
            </AuthenticatedLayout>
        );
    }

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
                    <Col span={3}>
                        <Typography.Text strong>Bulan Masuk</Typography.Text>
                        <DatePicker
                            style={{ width: "100%" }}
                            allowClear
                            value={
                                selectedYearMonth
                                    ? dayjs(selectedYearMonth, "YYYY-MM")
                                    : null
                            }
                            onChange={(date, dateString) => {
                                setSelectedYearMonth(dateString);
                            }}
                            picker="month"
                            placeholder="Pilih Bulan/Tahun"
                            disabledDate={(current) =>
                                current && current > moment().endOf("day")
                            }
                        />
                    </Col>
                    <Col span={3}>
                        <Typography.Text strong>Nomer RM</Typography.Text>
                        <Input
                            allowClear
                            placeholder="Nomor RM"
                            value={selectedNoRM}
                            onChange={(e) => {
                                const value = e.target.value;
                                setSelectedNoRM(value);
                            }}
                        />
                    </Col>
                    <Col span={4}>
                        <Typography.Text strong>Status</Typography.Text>
                        <Select
                            defaultValue={selectedStatusRawat}
                            style={{ width: "100%" }}
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
                    <Col span={4}>
                        <Typography.Text strong>Bangsal</Typography.Text>
                        <Select
                            value={selectedBangsal}
                            style={{ width: "100%" }}
                            options={optionsBangsal}
                            onChange={(value) => setSelectedBangsal(value)}
                        />
                    </Col>
                    <Col span={2}>
                        <Typography.Text strong>&nbsp;</Typography.Text>
                        <Button block type="primary" onClick={handleCari}>
                            Cari
                        </Button>
                    </Col>
                    <Col span={2}>
                        <Typography.Text strong>&nbsp;</Typography.Text>
                        <Button block onClick={hadleCetakKlaim}>
                            Download XLS
                        </Button>
                    </Col>
                </Row>
                <small>
                    total data: {totalData}. Page: {page}. Perpage: {perPage}
                </small>
                <Table
                    bordered
                    loading={loadingFetchData}
                    dataSource={dataSource}
                    columns={columns}
                    size="small"
                    rowKey="FTNO_TRANSAKSI"
                    scroll={{
                        x: 2000,
                        y: scrollY,
                    }}
                    pagination={{
                        simple: true,
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
                        // onChange={(e) => setModalUpdateValue(e.target.value)} // Update the state with the new value
                    />
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
