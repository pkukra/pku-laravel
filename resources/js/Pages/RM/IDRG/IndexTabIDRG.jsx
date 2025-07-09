import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification, Divider } from "antd";
import axios from "axios";

import DiagnosaListIDRG from "./DiagnosaListIDRG";
import ProcedureListIDRG from "./ProcedureListIDRG";

function Index({
    pasien,
    setDisableINACBG,
    fetchIDRGData,
    fetchINACBGData,
    idrgGroupData,
    loadingFetchIdrgData,
    isKlaimFinal,
    reFetchPasien,
}) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);
    const [bridgingLoading, setBridgingLoading] = useState(false);
    const [diagnosaTab, setDiagnosaTab] = useState([]);

    const [modalFinalOpen, setModalFinalOpen] = useState(false);
    const [finalLoading, setFinalLoading] = useState(false);

    const [finalUmumLoading, setFinalUmumLoading] = useState(false);

    const [modalReEditIDRGOpen, setModalReEditIDRGOpen] = useState(false);
    const [reeditLoading, setReeditLoading] = useState(false);

    const no_sep = pasien?.FMNOSEP || null;
    let customer_id = pasien?.FRPCUSTOMER_ID;
    if (pasien?.JENIS_RAWAT == "ranap") {
        customer_id = pasien?.PRWIKD_CUSTOMER;
    }

    const handleBridgingData = async () => {
        setBridgingLoading(true);
        let routeName = "rm.pasien-rujukan.bridging_data_idrg";
        if (pasien?.JENIS_RAWAT == "ranap") {
            routeName = "rm.pasien-inap.bridging_data_idrg";
        }
        try {
            const response = await axios.post(
                route(routeName, {
                    no_sep: no_sep,
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
                description: "Sukses bridging and grouping IDRG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setBridgingLoading(false);
            setModalBridgeOpen(false);
            fetchIDRGData();
        }
    };

    const handleFinalData = async () => {
        setFinalLoading(true);

        let routeName = "rm.pasien-rujukan.bridging_final_idrg";
        if (pasien?.JENIS_RAWAT == "ranap") {
            routeName = "rm.pasien-inap.bridging_final_idrg";
        }
        try {
            const response = await axios.post(
                route(routeName, {
                    no_sep: no_sep,
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
                description: "Sukses final IDRG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setFinalLoading(false);
            setModalFinalOpen(false);
            fetchIDRGData();
        }
    };

    const handleFinalPasienUmum = async () => {
        setFinalUmumLoading(true);

        let routeName = "rm.pasien-rujukan.final_pasien_umum";
        let kode_reg = pasien?.FRPNOTRANSAKSI;
        let kode_reg_kj = pasien?.FRPNOTRANSAKSIKJ;

        if (pasien?.JENIS_RAWAT == "ranap") {
            routeName = "rm.pasien-inap.final_pasien_umum";
            kode_reg = pasien?.FTNO_TRANSAKSI;
            kode_reg_kj = pasien?.FTNO_TRANSAKSI;
        }
        try {
            const response = await axios.post(route(routeName), {
                kode_reg,
                kode_reg_kj,
            });
            if (response?.data?.status == "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.message,
                });
            }

            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Final data Berhasil",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            reFetchPasien();
            setFinalUmumLoading(false);
        }
    };

    const handleEditUlangData = async () => {
        setReeditLoading(true);
        let routeName = "rm.pasien-rujukan.edit_ulang_idrg";
        if (pasien?.JENIS_RAWAT == "ranap") {
            routeName = "rm.pasien-inap.edit_ulang_idrg";
        }
        try {
            const response = await axios.post(
                route(routeName, {
                    no_sep: no_sep,
                })
            );

            if (response?.data?.status == "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code == 400) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Sukses Edit Ulang IDRG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setReeditLoading(false);
            setModalReEditIDRGOpen(false);
            fetchIDRGData();
            fetchINACBGData();
        }
    };

    const isFinalIDRG = idrgGroupData?.is_final == 1;

    const disableBridgeButton = () => {
        if (isFinalIDRG) {
            return true; // Disable if already finalized
        }
        if (diagnosaTab.length == 0) {
            return true; // Disable if no diagnoses
        }
        if (!["X002", "X003"].includes(customer_id)) {
            return true; // Disable jika bukan X002 atau X003
        }
        return false; // Enable otherwise
    };

    const disableFinalButton = () => {
        if (isFinalIDRG) {
            return true; // Disable if already finalized
        }
        if (eklaim_group_data?.mdc_number === "36") {
            return true;
        }
        if (idrgGroupData === null) {
            return true;
        }
        return false; // Enable otherwise
    };

    useEffect(() => {
        if (no_sep) {
            fetchIDRGData();
        }
    }, [no_sep]);

    useEffect(() => {
        const isFinal = idrgGroupData?.is_final == "1";
        setDisableINACBG(!isFinal); // kebalikan: jika final, maka disable = false
    }, [idrgGroupData]);

    const eklaim_group_data = JSON.parse(
        idrgGroupData?.response_eklaim || "{}"
    );

    return (
        <>
            <h3>iDRG</h3>
            <Row gutter={12}>
                <Col span={12}>
                    <DiagnosaListIDRG
                        isFinalIDRG={isFinalIDRG}
                        pasien={pasien}
                        setDiagnosaTab={setDiagnosaTab}
                        fetchIDRGData={fetchIDRGData}
                    />
                </Col>
                <Col span={12}>
                    <ProcedureListIDRG
                        pasien={pasien}
                        isFinalIDRG={isFinalIDRG}
                        fetchIDRGData={fetchIDRGData}
                    />
                </Col>
            </Row>
            <Row gutter={12} style={{ marginTop: 16 }}>
                <Col span={12} />
                <Col span={12}>
                    {customer_id != "X002" && customer_id != "X003" ? (
                        <div style={{ marginBottom: 8 }}>
                            <p>
                                <strong>Pasien Non BPJS</strong>
                            </p>
                            <p>
                                <strong>Status :</strong>{" "}
                                {pasien?.FKUNCI_VALIDASI == "1"
                                    ? "Final"
                                    : "Belum Final"}
                            </p>

                            <Button
                                loading={finalUmumLoading}
                                disabled={finalUmumLoading}
                                key="submit"
                                type="primary"
                                onClick={handleFinalPasienUmum}
                                style={{ background: "#33cc33" }}
                            >
                                Final Pasien Non BPJS
                            </Button>
                        </div>
                    ) : (
                        <></>
                    )}
                </Col>
            </Row>
            <Modal
                open={modalBridgeOpen}
                title="Bridging Data IDRG"
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
                        key="submit"
                        type="primary"
                        loading={bridgingLoading}
                        disabled={!no_sep}
                        onClick={handleBridgingData}
                        style={{ background: "#33cc33" }}
                    >
                        Ok, Bridge & Grouping Data
                    </Button>,
                ]}
            >
                <p>
                    <strong>Nomor SEP:</strong>{" "}
                    {no_sep || <span>Belum ada data SEP</span>}
                </p>
            </Modal>
            <Modal
                open={modalFinalOpen}
                title="Final Data IDRG"
                onCancel={() => setModalFinalOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalFinalOpen(false)}
                        loading={finalLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        key="submit"
                        type="primary"
                        loading={finalLoading}
                        disabled={!no_sep}
                        onClick={handleFinalData}
                        style={{ background: "#cc66ff" }}
                    >
                        Ok, Final Data
                    </Button>,
                ]}
            >
                <p>
                    <strong>Nomor SEP:</strong>{" "}
                    {no_sep || <span>Belum ada data SEP</span>}
                </p>
            </Modal>
            <Modal
                open={modalReEditIDRGOpen}
                title="Edit Ulang iDRG"
                onCancel={() => setModalReEditIDRGOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalReEditIDRGOpen(false)}
                        loading={reeditLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        type="primary"
                        loading={reeditLoading}
                        style={{ background: "#F3732F" }}
                        onClick={handleEditUlangData}
                    >
                        Ok, Edit Ulang iDRG
                    </Button>,
                ]}
            >
                <p>
                    <strong>Nomor SEP:</strong>{" "}
                    {no_sep || <span>Belum ada data SEP</span>}
                </p>
            </Modal>
        </>
    );
}

export default Index;
