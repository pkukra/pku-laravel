import React, { useState, useEffect } from "react";
import { Modal, Button } from "antd";

export default function Index({ pasien }) {
    const [hasilRadiologiData, setHasilRadiologiData] = useState([]);
    const [loadingHasilRadiologi, setLoadingHasilRadiologi] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);

    const fetchHasilRadiologi = async () => {
        setLoadingHasilRadiologi(true);
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.get_hasil_radiologi", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            );
            setHasilRadiologiData(response?.data?.data || []);
        } catch (error) {
            console.error("Error fetching hasil radiologi:", error);
        } finally {
            setLoadingHasilRadiologi(false);
        }
    };

    useEffect(() => {
        fetchHasilRadiologi();
    }, []);

    return (
        <>
            <Button
                style={{ margin: 2 }}
                type="primary"
                onClick={() => {
                    setModalOpen(true);
                    fetchHasilRadiologi();
                }}
            >
                Hasil Radilogi
            </Button>

            {/* Modal untuk menampilkan detail hasil radiologi */}
            <Modal
                loading={loadingHasilRadiologi}
                title="Detail Hasil Radiologi"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={800}
            >
                <table
                    className="tw-table tw-table-xs"
                    style={{ width: "100%" }}
                >
                    <tbody align="left">
                        {hasilRadiologiData.length < 1 ? (
                            <tr>
                                <td>Tidak ada hasil radiologi</td>
                            </tr>
                        ) : (
                            hasilRadiologiData.map((item, index) => (
                                <tr key={index}>
                                    <td
                                        style={{
                                            width: "25%",
                                            verticalAlign: "top",
                                        }}
                                    >
                                        {item?.MRHNO_TRANSAKSI}
                                    </td>
                                    <td
                                        style={{ verticalAlign: "top" }}
                                        dangerouslySetInnerHTML={{
                                            __html: item?.MRHHASIL,
                                        }}
                                    ></td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </Modal>
        </>
    );
}
