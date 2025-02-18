import React, { useState, useEffect } from "react";
import { Modal, Card, Button, Tooltip, notification, Spin } from "antd";
import axios from "axios";

export default function Index({ pasien, user }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);

    const handleBridgingData = () => {};

    useEffect(() => {}, []);
    const disabled = user.eklaim_key ? false : true;

    return (
        <>
            <Card title={"Sync Data ke Ekalim"}>
                <Tooltip
                    title={disabled ? "User belum setup Eklaim Key" : "Tekan untuk bridgin data ke INACBG"}
                    placement="topLeft"
                >
                    <Button
                        type="primary"
                        onClick={() => setModalBridgeOpen(true)}
                        disabled={disabled}
                    >
                        Bridge Data
                    </Button>
                </Tooltip>
            </Card>

            <Modal
                title="Bridging Data Ke INACBG"
                open={modalBridgeOpen}
                onCancel={() => setModalBridgeOpen(false)}
                okText={"Ok, Kirim Data"}
                onOk={() => handleBridgingData()}
            ></Modal>
        </>
    );
}
