import React, { useState, useEffect } from "react";
import { Modal, Card, Button, Input, notification, Spin } from "antd";
import axios from "axios";

export default function Index({ pasien }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);

    const handleBridgingData = () => {};

    useEffect(() => {}, []);

    return (
        <>
            <Card title={"Sync Data ke Ekalim"}>
                <Button onClick={() => setModalBridgeOpen(true)}>
                    Bridge Data
                </Button>
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
