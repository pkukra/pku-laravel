import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification } from "antd";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";

import PasienRujukanDetailDiagnosaListIDRG from "./PasienRujukanDetailDiagnosaListIDRG";
import PasienRujukanDetailProcedureListIDRG from "./PasienRujukanDetailProcedureListIDRG";

function Index({ pasien, golbalSEP }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);
    const [bridgingLoading, setBridgingLoading] = useState(false);

    const handleBridgingData = async () => {
        setBridgingLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_data_idrg", {
                    no_sep: golbalSEP,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "topRight",
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

    return (
        <>
            <p>
                <strong>iDRG</strong>
            </p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <PasienRujukanDetailDiagnosaListIDRG pasien={pasien} />
                </Col>
                <Col span={12}>
                    <PasienRujukanDetailProcedureListIDRG pasien={pasien} />
                </Col>
            </Row>
            <Row gutter={[5, 5]} style={{ marginTop: 20 }}>
                <Col span={12}></Col>
                <Col span={12}>
                    <Button
                        disabled={
                            pasien.FRPCUSTOMER_ID == "X002" ? false : true
                        }
                        type="primary"
                        onClick={() => {
                            setModalBridgeOpen(true);
                            return;
                        }}
                        style={{ marginRight: 5, backgroundColor: " #33cc33" }}
                    >
                        Bridge iDRG
                    </Button>
                </Col>
            </Row>

            <Modal
                open={modalBridgeOpen}
                title="Bridging Data Ke INACBG"
                onCancel={() => setModalBridgeOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalBridgeOpen(false)}
                        loading={bridgingLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        disabled={golbalSEP !== null ? false : true}
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
                {golbalSEP ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {golbalSEP}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>
        </>
    );
}

export default Index;
