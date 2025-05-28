import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification, Divider } from "antd";
import axios from "axios";

import DiagnosaListIDRG from "./DiagnosaListIDRG";
import ProcedureListIDRG from "./ProcedureListIDRG";

function Index({ pasien, golbalSEP }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);
    const [bridgingLoading, setBridgingLoading] = useState(false);
    const [diagnosaTab, setDiagnosaTab] = useState([]);

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

    const disableBridgeButton = () => {
        if (diagnosaTab.length === 0) {
            return true; // Disable if no diagnoses
        }
        if (pasien.FRPCUSTOMER_ID !== "X002") {
            return true; // Disable unless specific customer
        }
        return false; // Enable otherwise
    };

    const eklaim_group_data = JSON.parse(pasien?.response_eklaim || "{}");

    return (
        <>
            <p>
                <strong>iDRG</strong>
            </p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <DiagnosaListIDRG
                        pasien={pasien}
                        setDiagnosaTab={setDiagnosaTab}
                    />
                </Col>
                <Col span={12}>
                    <ProcedureListIDRG pasien={pasien} />
                </Col>
            </Row>

            <Row gutter={[5, 5]}>
                <Col span={12}></Col>
                <Col span={12}>
                    <Divider> Hasil Grouping iDRG </Divider>
                    <table
                        style={{
                            borderCollapse: "collapse",
                            width: "100%",
                            margin: 10,
                        }}
                    >
                        <tbody>
                            <tr>
                                <td style={{ width: "15%" }}>MDC Number</td>
                                <td>{eklaim_group_data?.mdc_number}</td>
                            </tr>
                            <tr>
                                <td
                                    style={{
                                        verticalAlign: "top",
                                    }}
                                >
                                    MDC Description
                                </td>
                                <td
                                    style={{
                                        verticalAlign: "top",
                                    }}
                                >
                                    {eklaim_group_data?.mdc_description}
                                </td>
                            </tr>
                            <tr>
                                <td>DRG Code</td>
                                <td>{eklaim_group_data?.drg_code}</td>
                            </tr>
                            <tr>
                                <td>DRG Description</td>
                                <td>{eklaim_group_data?.drg_description}</td>
                            </tr>
                        </tbody>
                    </table>
                    <Divider />
                    <Button
                        disabled={disableBridgeButton()}
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
