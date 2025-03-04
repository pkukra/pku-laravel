import React, { useState, useEffect } from "react";
import { Modal, Spin, Card, Input, notification, Button } from "antd";
import { EditOutlined } from "@ant-design/icons";
import axios from "axios";

export default function Index({ pasien }) {
    const [fetchDataLoading, setFetchDataLoading] = useState(false);
    const [data, setData] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);

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
                setFetchDataLoading(true);
            });

        return;
    };

    const handleClick = () => {
        setModalOpen(true);
        fetchData();
    };

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
                {JSON.stringify(data)}
            </Modal>
        </>
    );
}
