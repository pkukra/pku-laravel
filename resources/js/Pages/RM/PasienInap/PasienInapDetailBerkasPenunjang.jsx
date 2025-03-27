import React, { useState, useEffect } from "react";
import { Card, Button, Modal, Skeleton } from "antd";

import PasienInapDetailHasilRadiologi from "./PasienInapDetailHasilRadiologi";
import PasienInapDetailCPPT from "./PasienInapDetailCPPT";

export default function Index({ pasien }) {
    const [modalOpen, setModalOpen] = useState(false);

    const [modalBerkasRMOpen, setModalBerkasRMOpen] = useState(false);
    const [selectedBerkasRMFile, setSelectedBerkasRMFile] = useState(null);

    const [listBerkasRM, setListBerkasRM] = useState([]);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading

    // Fungsi untuk mengambil data diagnosa
    const fetcListBerkasRM = () => {
        axios
            .get(
                route("rm.pasien-inap.get_berkas_rm", {
                    kode_reg: pasien.PRWINO_TRANSAKSI,
                })
            )
            .then((response) => {
                setListBerkasRM(response?.data?.data);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {});
    };

    const handleSelectBerkasRM = (berkas) => {
        setSelectedBerkasRMFile(berkas?.att_name);
        setModalBerkasRMOpen(true);
        return;
    };

    useEffect(() => {
        fetcListBerkasRM();
    }, []);

    return (
        <>
            <Card title="Berkas Panunjang">
                {/* Button untuk membuka modal */}
                <Button type="primary" onClick={() => setModalOpen(true)}>
                    Hasil Lab
                </Button>

                <PasienInapDetailHasilRadiologi pasien={pasien} />
                <PasienInapDetailCPPT pasien={pasien} />

                {listBerkasRM.map((berkas) => (
                    <Button
                        key={berkas.FS_KD_TRS}
                        type="primary"
                        onClick={() => handleSelectBerkasRM(berkas)}
                        style={{ marginRight: 2 }}
                    >
                        {berkas.FS_KETERANGAN}
                    </Button>
                ))}

                {/* Modal Ant Design */}
                <Modal
                    title="Preview Hasil Lab"
                    open={modalOpen}
                    onCancel={() => setModalOpen(false)}
                    footer={null}
                    width={800}
                >
                    {/* Loading Indicator */}
                    {loadingPdf && (
                        <>
                            <Skeleton active />
                        </>
                    )}

                    {/* PDF Viewer */}
                    <iframe
                        src={`http://10.10.10.10/emr/index.php/penunjang/lab_no_auth/hasil_laborat_ranap_lis/${pasien?.PRWINO_TRANSAKSI}`}
                        width="100%"
                        height="600px"
                        style={{
                            border: "none",
                            display: loadingPdf ? "none" : "block",
                        }}
                        onLoad={() => setLoadingPdf(false)} // Sembunyikan loading saat PDF selesai dimuat
                    ></iframe>
                </Modal>

                <Modal
                    title="Preview Berkas RM"
                    open={modalBerkasRMOpen}
                    onCancel={() => setModalBerkasRMOpen(false)}
                    footer={null}
                    width={900}
                    destroyOnClose
                >
                    {/* Loading Indicator */}
                    {loadingPdf && (
                        <>
                            <Skeleton active />
                        </>
                    )}

                    {/* PDF Viewer */}
                    <iframe
                        src={`http://10.10.10.10/emr/resource/doc/rm/${selectedBerkasRMFile}`}
                        width="100%"
                        height="600px"
                        style={{
                            border: "none",
                            display: loadingPdf ? "none" : "block",
                        }}
                        onLoad={() => setLoadingPdf(false)} // Sembunyikan loading saat PDF selesai dimuat
                    ></iframe>
                </Modal>
            </Card>
        </>
    );
}
