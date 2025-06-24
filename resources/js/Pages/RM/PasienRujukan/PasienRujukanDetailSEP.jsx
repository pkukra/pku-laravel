import React, { useState, useEffect } from "react";
import { Modal, Card, Button, Tooltip, notification, Input } from "antd";
import axios from "axios";

export default function Index({ pasien, user, reFetchPasien }) {
    const [loadingSep, setLoadingSep] = useState(false);
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);
    const [modalFinalOpen, setModalFinalOpen] = useState(false);
    const [bridgingLoading, setBridgingLoading] = useState(false);
    const [finalLoading, setFinalLoading] = useState(false);
    const [noSep, setNoSep] = useState(null);
    const [noSepBaru, setNoSepBaru] = useState(null);
    const [modalUpdateNoSEPOpen, setModalUpdateNoSEPOpen] = useState(false);
    const [loadingUpdateNoSep, setLoadingUpdateNoSep] = useState(false);

    const fetchNoSep = async () => {
        setLoadingSep(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_nomer_sep", {
                    kode_reg: pasien.FRPNOTRANSAKSI,
                    kode_reg_kj: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setNoSep(response?.data?.data?.FMNOSEP);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingSep(false);
            });
    };

    const handleBridgingData = async () => {
        setBridgingLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_data_process", {
                    no_sep: noSep,
                })
            );

            console.log(response?.data);

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    // message: "Peringatan!",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "topRight",
                    // message: "Peringatan!",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            reFetchPasien();
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

    const handleFinalData = async () => {
        setFinalLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_final_process", {
                    no_sep: noSep,
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
                    // message: "Peringatan!",
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
            setFinalLoading(false);
            setModalFinalOpen(false);
        }
    };

    const handleUbahSep = async () => {
        if (!noSepBaru) {
            return;
        }
        setLoadingUpdateNoSep(true);
        try {
            const response = await axios.put(
                route("rm.pasien-rujukan.update_nomer_sep", {
                    kode_reg: pasien?.FRPNOTRANSAKSI,
                    kode_reg_kj: pasien?.FRPNOTRANSAKSIKJ,
                }),
                {
                    no_rm: pasien?.FRPPASIEN_ID,
                    new_sep: noSepBaru,
                    poli: pasien?.FMPKLINIKN,
                    dpjp: pasien?.FMDDOKTERN,
                }
            );
            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.message,
                });
            }

            fetchNoSep();
            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Update Nomer SEP Berhasil",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoadingUpdateNoSep(false);
            setModalUpdateNoSEPOpen(false);
            setNoSepBaru(null);
        }
    };

    const RupiahFormat = (x) => {
        const number = Number(x);
        const formatted = new Intl.NumberFormat("id-ID").format(number);
        return formatted;
    };

    useEffect(() => {
        fetchNoSep();
    }, []);

    let ketSep = "";
    if (pasien.FRPCUSTOMER_ID == "X002" || pasien.FRPCUSTOMER_ID == "X003") {
        ketSep = noSep == null ? "Belum ada SEP" : `No SEP: ${noSep}`;
    } else {
        ketSep = "Bukan Pasien BPJS";
    }

    const disabled = !user.eklaim_key;

    return (
        <>
            <Card title={"INACBG/BPJS/SEP"} loading={loadingSep}>
                <p>{ketSep} </p>

                {ketSep == "Bukan Pasien BPJS" ? (
                    <>
                    </>
                ) : (
                    <><p>
                    KODE INAGROUPER:{" "}
                    <strong>{pasien?.FTKODEINACBG}</strong>
                </p>
                <p>
                    Tarif INACBG: &nbsp;&nbsp;{" "}
                    <strong>
                        Rp {RupiahFormat(pasien?.FTTARIPINACBG)}
                    </strong>
                </p></>
                )}

                {/* <Tooltip
                    title={
                        disabled
                            ? "User belum setup Eklaim Key"
                            : "Tekan untuk bridgin data ke INACBG"
                    }
                    placement="topLeft"
                >
                    <Button
                        type="primary"
                        onClick={() => setModalUpdateNoSEPOpen(true)}
                        style={{ marginRight: 5 }}
                    >
                        Ubah SEP
                    </Button>

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
                </Tooltip> */}
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

            <Modal
                destroyOnClose
                closable={false}
                open={modalUpdateNoSEPOpen}
                title="Edit Nomer SEP"
                footer={[
                    <Button
                        loading={loadingUpdateNoSep}
                        key="back"
                        onClick={() => setModalUpdateNoSEPOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        loading={loadingUpdateNoSep}
                        key="submit"
                        type="primary"
                        onClick={handleUbahSep}
                    >
                        Simpan
                    </Button>,
                ]}
            >
                <p>{ketSep}</p>
                <Input
                    placeholder="Nomer SEP BARU"
                    value={noSepBaru}
                    onChange={(e) => setNoSepBaru(e.target.value)}
                />
            </Modal>
        </>
    );
}
