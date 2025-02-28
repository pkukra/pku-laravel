import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { Table, Card, Modal, Input, Button } from "antd";
import { EditOutlined } from "@ant-design/icons";
import axios from "axios";
import moment from "moment";

const { TextArea } = Input;

export default function Index({ auth }) {
    const [dataSource, setDataSource] = useState([]);
    const [loadingFetchData, setLoadingFetchData] = useState(false);
    const [openModalUpdate, setOpenModalUpdate] = useState(false);

    const [modalUpdateKey, setModalUpdateKey] = useState(null);
    const [modalUpdateKodeReg, setModalUpdateKodeReg] = useState(null);
    const [modalUpdateValue, setModalUpdateValue] = useState(null);

    const columns = [
        {
            title: "Nama Pasien",
            dataIndex: "NAMAPASIEN",
            key: "NAMAPASIEN",
            fixed: "left",
            align: "top",
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
            fixed: "left",
            render: (text) => moment(text).format("D-M-YYYY"),
        },
        {
            title: "Tanggal Keluar",
            dataIndex: "PRWITGL_KELUAR",
            key: "PRWITGL_KELUAR",
            fixed: "left",
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
            dataIndex: "DIAGNOSA",
            key: "DIAGNOSA",
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
            dataIndex: "DPJP",
            key: "DPJP",
        },
        {
            title: "Pemeriksaan Penunjang",
            dataIndex: "LOS",
            key: "DPJP",
        },
        {
            title: "Hasil Penunjang Abnormal",
            dataIndex: "LOS",
            key: "DPJP",
        },
        {
            title: "Hak Kelas",
            dataIndex: "LOS",
            key: "DPJP",
        },
        {
            title: "Naik Kelas",
            dataIndex: "LOS",
            key: "DPJP",
        },
        {
            title: "Kemungkinan Kode Dignosis",
            dataIndex: "LOS",
            key: "DPJP",
        },
        {
            title: "Kemungkinan Kode Prosedur",
            dataIndex: "LOS",
            key: "DPJP",
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

    const handleOpenModal = (param) => {
        setModalUpdateKey(param?.key);
        setModalUpdateKodeReg(param?.kode_reg);
        setModalUpdateValue(param?.value);
        setOpenModalUpdate(true);
    };

    const handleUpdateModal = () => {
        let payload = {};
        if (modalUpdateKey === "diagnosa_sekunder") {
            payload = {
                diagnosa_sekunder: modalUpdateValue,
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
            .catch((error) => {});
    };

    const fetchData = async (noRmValue) => {
        setLoadingFetchData(true);
        try {
            const response = await axios.get(
                route("casemix.ranap-monit.list_pasien_data", {
                    no_rm: noRmValue,
                })
            );
            setDataSource(response?.data?.pasiens || []);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoadingFetchData(false);
        }
    };

    useEffect(() => {
        fetchData();
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
                <Table
                    bordered
                    loading={loadingFetchData}
                    pagination={false}
                    dataSource={dataSource}
                    columns={columns}
                    size="small"
                    rowKey="PRWINO_TRANSAKSI"
                    scroll={{
                        x: 2000,
                        y: 600,
                    }}
                />
            </Card>
            <Modal
                destroyOnClose
                title={modalUpdateKey}
                open={openModalUpdate}
                closable={false}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setOpenModalUpdate(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        key="submit"
                        type="primary"
                        onClick={handleUpdateModal}
                    >
                        Simpan
                    </Button>,
                ]}
            >
                <p>{modalUpdateKodeReg}</p>
                <TextArea
                    rows={4}
                    value={modalUpdateValue}
                    onChange={(e) => setModalUpdateValue(e.target.value)} // Update the state with the new value
                />
            </Modal>
        </AuthenticatedLayout>
    );
}
