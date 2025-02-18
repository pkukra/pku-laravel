import React, { useState, useEffect } from "react";
import { Modal, Card, Button, Tooltip, notification, Spin } from "antd";
import axios from "axios";

export default function Index({ pasien, user, noSep }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);
    const [modalFinalOpen, setModalFinalOpen] = useState(false);
    const [bridgingLoading, setBridgingLoading] = useState(false);
    const [finalLoading, setFinalLoading] = useState(false);

    const handleBridgingData = async () => {
        setBridgingLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_data_process", {
                    no_sep: noSep,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "bottomRight",
                    message: "Peringatan!",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "bottomRight",
                    message: "Peringatan!",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "bottomRight",
                message: "Sukses!",
                description: response?.data?.response?.metadata?.message,
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setBridgingLoading(false);
            setModalBridgeOpen(false);
        }
    };

    const handleFinalData = async () => {
        setFinalLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_final_process", {
                    no_sep: noSep,
                })
            );

            console.log(response?.data);

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "bottomRight",
                    message: "Peringatan!",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "bottomRight",
                    message: "Peringatan!",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "bottomRight",
                message: "Sukses!",
                description: response?.data?.response?.metadata?.message,
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setFinalLoading(false);
            setModalFinalOpen(false);
        }
    };

    useEffect(() => {}, []);
    const disabled = !user.eklaim_key

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
                        disabled={disabled || !noSep}
                        style={{ marginRight: 5, backgroundColor: " #33cc33" }}
                    >
                        {!noSep ? "Belum ada SEP" : "Bridge Data"}
                    </Button>

                    <Button
                        type="primary"
                        onClick={() => setModalFinalOpen(true)}
                        disabled={disabled || !noSep}
                        style={{ backgroundColor: " #cc66ff" }}
                    >
                        {!noSep ? "Belum ada SEP" : "Final Data"}
                    </Button>
                </Tooltip>
            </Card>

            <Modal
                title="Bridging Data Ke INACBG"
                open={modalBridgeOpen}
                onCancel={() => setModalBridgeOpen(false)}
                okText={"Ok, Kirim Data"}
                onOk={() => handleBridgingData()}
                loading={bridgingLoading}
            >
                {noSep}
            </Modal>

            <Modal
                title="Final Data Klaim Di INACBG"
                open={modalFinalOpen}
                onCancel={() => setModalFinalOpen(false)}
                okText={"Ok, Final Data Klaim"}
                onOk={() => handleFinalData()}
                loading={finalLoading}
            >
                {noSep}
            </Modal>
        </>
    );
}
