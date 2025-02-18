import React, { useState, useEffect } from "react";
import { Modal, Card, Button, Tooltip, notification, Spin } from "antd";
import axios from "axios";

export default function Index({ pasien, user, noSep }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleBridgingData = async () => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_data_process", {
                    no_sep: noSep,
                })
            );
            console.log(response?.data);
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {}, []);
    let disabled = !user.eklaim_key || noSep === "";

    return (
        <>
            <Card title={"Sync Data ke Ekalim"}>
                <Tooltip
                    title={
                        disabled
                            ? "User belum setup Eklaim Key"
                            : "Tekan untuk bridgin data ke INACBG"
                    }
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
                loading={loading}
            >
                {noSep}
            </Modal>
        </>
    );
}
