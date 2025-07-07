import React, { useState, useEffect } from "react";
import { Card, Button, Modal, Skeleton } from "antd";

import PasienRujukanDetailHasilRadiologi from "./PasienRujukanDetailHasilRadiologi";
import PasienRujukanDetailObat from "./PasienRujukanDetailObat";
import PasienRujukanDetailIMR from "./PasienRujukanDetailIMR";
import PasienRujukanDetailIHistoricalProcedure from "./PasienRujukanDetailIHistoricalProcedure";

export default function Index({ dataTransaksi, pasien }) {
    const [hasilLabUrl, setHasilLabUrl] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true);

    const [lodingFetchPermintaan, setLodingFetchPermintaan] = useState(false);
    const [dataPermintaan, setDataPermintaan] = useState([]);

    const generateLabUrl = async () => {
        try {
            const response = await axios.get(route("common.lab_url"));
            setHasilLabUrl(
                response?.data?.data + dataTransaksi?.FRPNOTRANSAKSI
            );
        } catch (error) {
            console.error("Error fetching lab data:", error);
        }
    };

    const fetchPermintaanLab = async () => {
        setLodingFetchPermintaan(true);
        try {
            const response = await axios.get(
                route("rm.get_permintaan_rad_n_lab", {
                    kode_reg: dataTransaksi?.FRPNOTRANSAKSI,
                })
            );
            setDataPermintaan(response?.data || []);
        } catch (error) {
            console.error("Error fetching hasil permintaan lab:", error);
        } finally {
            setLodingFetchPermintaan(false);
        }
    };

    useEffect(() => {
        generateLabUrl();
        fetchPermintaanLab();
    }, []);

    return (
        <>
            <Card
                title="Permintaan Panunjang DPJP"
                loading={lodingFetchPermintaan}
            >
                <p>
                    <strong>Permintaan Lab:</strong>{" "}
                </p>
                {dataPermintaan?.lab?.length < 1 && (
                    <span>Tidak ada permintaan lab.</span>
                )}
                <ol>
                    {dataPermintaan?.lab?.map((item, key) => (
                        <li key={key}>{item?.FMPPRODUKN}</li>
                    ))}
                </ol>
                <p>
                    <strong>Permintaan Radiologi:</strong>{" "}
                </p>
                {dataPermintaan?.radiologi?.length < 1 && (
                    <span>Tidak ada permintaan radiologi.</span>
                )}
                <ol>
                    {dataPermintaan?.radiologi?.map((item, key) => (
                        <li key={key}>{item?.FMPPRODUKN}</li>
                    ))}
                </ol>
            </Card>
            <Card title="Hasil Panunjang" style={{ marginTop: 10 }}>
                {/* Button untuk membuka modal */}
                <Button
                    style={{ margin: 2 }}
                    type="primary"
                    onClick={() => {
                        setModalOpen(true);
                        return;
                    }}
                    disabled={!hasilLabUrl}
                >
                    Hasil Lab
                </Button>

                <PasienRujukanDetailObat dataTransaksi={dataTransaksi} />
                <PasienRujukanDetailHasilRadiologi
                    dataTransaksi={dataTransaksi}
                />
                <PasienRujukanDetailIMR dataTransaksi={dataTransaksi} />
                <PasienRujukanDetailIHistoricalProcedure pasien={pasien} />

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
                        src={hasilLabUrl}
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
