import React, { useState, useEffect } from "react";
import {
    Modal,
    Spin,
    Row,
    Col,
    Input,
    notification,
    Button,
    Table,
} from "antd";
import { EditOutlined, LoadingOutlined, PlusOutlined } from "@ant-design/icons";
import axios from "axios";

export default function Index({ pasien }) {
    const [fetchDataLoading, setFetchDataLoading] = useState(false);
    const [data, setData] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);

    const [keteranganProduk, setKeteranganProduk] = useState(null);
    const [nominalProduk, setNominalProduk] = useState(null);
    const [loadingSave, setLoadingSave] = useState(false);

    // Fungsi untuk mengambil data billing sementara dari tabel CASEMIX_BILLING_TEMP
    const fetchData = () => {
        setFetchDataLoading(true);
        axios
            .get(
                route("casemix.ranap-monit.list_billing_temp", {
                    kode_reg: pasien?.FTNO_TRANSAKSI,
                })
            )
            .then((response) => {
                console.log(response?.data);

                setData(response?.data?.data || []);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setFetchDataLoading(false);
            });

        return;
    };

    const handleClick = () => {
        setModalOpen(true);
        fetchData();
    };

    const handleSave = async () => {
        setLoadingSave(true);
        try {
            const response = await axios.post(
                route("casemix.ranap-monit.save_billing_temp"),
                {
                    NO_TRANSAKSI: pasien.FTNO_TRANSAKSI,
                    KETERANGAN: keteranganProduk,
                    NOMINAL: nominalProduk,
                }
            );
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoadingSave(false);
            fetchData();
        }
    };

    const handlehapus = async (id) => {
        try {
            const response = await axios.delete(
                route("casemix.ranap-monit.delete_billing_temp", {
                    id,
                })
            );
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            fetchData();
        }
    };

    const columns = [
        {
            title: "Keterangan",
            dataIndex: "KETERANGAN",
            key: "KETERANGAN",
        },
        {
            title: "Nominal",
            dataIndex: "NOMINAL",
            key: "NOMINAL",
            align: "right",
        },
        {
            title: "Action",
            key: "action",
            align: "center",
            render: (_, record) => (
                <Button
                    size="small"
                    variant="outlined"
                    color="danger"
                    onClick={() => handlehapus(record.ID)}
                >
                    hapus
                </Button>
            ),
        },
    ];

    return (
        <>
            <a onClick={() => handleClick()}>
                <EditOutlined />
            </a>

            <Modal
                title="Edit Billing Sementara"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                width={800}
                footer={[
                    <Button key="back" onClick={() => setModalOpen(false)}>
                        Cancel
                    </Button>,
                ]}
            >
                <p>
                    No RM: <strong>{pasien?.FTKD_PASIEN} </strong> Nama Pasien:{" "}
                    <strong>{pasien?.NAMAPASIEN}</strong> No Transakasi:{" "}
                    <strong>{pasien?.FTNO_TRANSAKSI} </strong>
                </p>

                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={15}>
                        <Input
                            placeholder="Nama Produk"
                            value={keteranganProduk}
                            onChange={(e) =>
                                setKeteranganProduk(e.target.value)
                            }
                        />
                    </Col>
                    <Col span={5}>
                        <Input
                            placeholder="Nominal"
                            value={nominalProduk}
                            onChange={(e) => setNominalProduk(e.target.value)}
                        />
                    </Col>
                    <Col span={4}>
                        <Button
                            type="primary"
                            size="medium"
                            style={{ width: "100%" }}
                            onClick={handleSave}
                            disabled={loadingSave}
                        >
                            {loadingSave ? (
                                <Spin
                                    indicator={<LoadingOutlined spin />}
                                    size="small"
                                />
                            ) : (
                                <PlusOutlined />
                            )}
                        </Button>
                    </Col>
                </Row>

                <Table
                    pagination={false}
                    columns={columns}
                    dataSource={data}
                    size="small"
                    loading={fetchDataLoading}
                    rowKey="ID"
                />
            </Modal>
        </>
    );
}
