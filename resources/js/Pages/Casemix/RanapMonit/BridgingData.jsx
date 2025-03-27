import React, { useState } from "react";
import { Modal, Button } from "antd";

export default function Index({ pasien, refetchData }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleBridgingData = async () => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-inap.bridging_data_process", {
                    no_sep: pasien?.NO_SEP,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "bottomRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "bottomRight",
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
            refetchData();
            setLoading(false);
            setModalOpen(false);
        }
    };

    return (
        <>
            <Button
                style={{ margin: 2 }}
                type="primary"
                size="small"
                onClick={() => {
                    setModalOpen(true);
                }}
            >
                Bridging
            </Button>

            <Modal
                title="Bridging Data"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                width={700}
                footer={[
                    <Button
                        key="back"
                        loading={loading}
                        onClick={() => setModalOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        key="submit"
                        type="primary"
                        loading={loading}
                        onClick={() => handleBridgingData()}
                        style={{ backgroundColor: " #33cc33" }}
                    >
                        Ok, Bridge Data
                    </Button>,
                ]}
            >
                <p>
                    No RM: <strong>{pasien?.FTKD_PASIEN} </strong> Nama Pasien:{" "}
                    <strong>{pasien?.NAMAPASIEN}</strong> No Transakasi:{" "}
                    <strong>{pasien?.FTNO_TRANSAKSI} </strong>
                </p>
                <p>No SEP: {pasien?.NO_SEP}</p>
            </Modal>
        </>
    );
}
