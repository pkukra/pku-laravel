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
        return;
    };

    useEffect(() => {
        fetchData();
    }, []);

    return (
        <>
            <a onClick={() => setModalOpen(true)}>
                <EditOutlined />
            </a>

            <Modal
                title="Edit Billing"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                width={800}
                footer={[
                    <Button key="back" onClick={() => setModalOpen(false)}>
                        Cancel
                    </Button>,
                ]}
            >
                hallo
            </Modal>
        </>
    );
}
