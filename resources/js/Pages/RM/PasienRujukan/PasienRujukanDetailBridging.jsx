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
    const disabled = !user.eklaim_key;

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
                closable={false}
                open={modalBridgeOpen}
                title="Bridging Data Ke INACBG"
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalBridgeOpen(false)}
                        loading={bridgingLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        key="submit"
                        type="primary"
                        loading={bridgingLoading}
                        onClick={() => handleBridgingData()}
                        style={{ backgroundColor: " #33cc33" }}
                    >
                        Ok, Bridging Data
                    </Button>,
                ]}
            >
                {noSep}
            </Modal>

            <Modal
                closable={false}
                open={modalFinalOpen}
                title="Final Klaim Di INACBG"
                footer={[
                    <Button
                        key="back"
                        loading={finalLoading}
                        onClick={() => setModalFinalOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        key="submit"
                        type="primary"
                        loading={finalLoading}
                        onClick={() => handleFinalData()}
                        style={{ backgroundColor: " #cc66ff" }}
                    >
                        Ok, Final Data
                    </Button>,
                ]}
            >
                {noSep}
            </Modal>
        </>
    );
}
